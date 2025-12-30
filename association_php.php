<?php
// ml/association_php.php

function runAssociationAnalysisPHP($csvFile) {
    // Log start time
    $startTime = microtime(true);
    
    // Cek file exists
    if (!file_exists($csvFile)) {
        return [
            'success' => false,
            'message' => 'File CSV tidak ditemukan: ' . $csvFile
        ];
    }
    
    // Baca data transaksi dari CSV
    $transactions = [];
    $productIndex = [];
    
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        // Skip header
        fgetcsv($handle);
        
        $rowCount = 0;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $rowCount++;
            $transactionId = $row[0] ?? '';
            $productName = trim($row[1] ?? '');
            
            // Skip jika data tidak valid
            if (empty($transactionId) || empty($productName)) {
                continue;
            }
            
            // Simpan ke transactions
            if (!isset($transactions[$transactionId])) {
                $transactions[$transactionId] = [];
            }
            
            // Tambah produk ke transaksi (tidak ada duplikat dalam transaksi yang sama)
            if (!in_array($productName, $transactions[$transactionId])) {
                $transactions[$transactionId][] = $productName;
            }
            
            // Update product index
            if (!isset($productIndex[$productName])) {
                $productIndex[$productName] = [];
            }
            
            if (!in_array($transactionId, $productIndex[$productName])) {
                $productIndex[$productName][] = $transactionId;
            }
        }
        fclose($handle);
    }
    
    $totalTransactions = count($transactions);
    
    if ($totalTransactions < 2) {
        return [
            'success' => false,
            'message' => 'Data transaksi terlalu sedikit (minimal 2 transaksi)',
            'count' => $totalTransactions
        ];
    }
    
    // Generate candidate rules
    $candidateRules = [];
    
    foreach ($transactions as $transactionId => $products) {
        $productCount = count($products);
        
        // Generate semua pasangan dalam transaksi yang sama
        for ($i = 0; $i < $productCount; $i++) {
            for ($j = 0; $j < $productCount; $j++) {
                if ($i != $j) {
                    $antecedent = $products[$i];
                    $consequent = $products[$j];
                    
                    // Gunakan key unik untuk menghindari duplikat
                    $key = $antecedent . '||' . $consequent;
                    
                    if (!isset($candidateRules[$key])) {
                        $candidateRules[$key] = [
                            'antecedent' => $antecedent,
                            'consequent' => $consequent,
                            'count_ab' => 0,
                            'count_a' => count($productIndex[$antecedent] ?? []),
                            'count_b' => count($productIndex[$consequent] ?? [])
                        ];
                    }
                    $candidateRules[$key]['count_ab']++;
                }
            }
        }
    }
    
    // Hitung metrik dan filter
    $results = [];
    $minConfidence = 0.3;   // Minimum 30% confidence
    $minSupport = 0.05;     // Minimum 5% support
    
    foreach ($candidateRules as $key => $rule) {
        if ($rule['count_a'] > 0) {
            $support = $rule['count_ab'] / $totalTransactions;
            $confidence = $rule['count_ab'] / $rule['count_a'];
            
            if ($confidence >= $minConfidence && $support >= $minSupport) {
                $results[] = [
                    'if_buy' => $rule['antecedent'],
                    'then_buy' => $rule['consequent'],
                    'confidence' => round($confidence, 3),
                    'support' => round($support, 3),
                    'count_ab' => $rule['count_ab'],
                    'count_a' => $rule['count_a'],
                    'count_b' => $rule['count_b'],
                    'total_transactions' => $totalTransactions
                ];
            }
        }
    }
    
    // Urutkan berdasarkan confidence tertinggi
    usort($results, function($a, $b) {
        return $b['confidence'] <=> $a['confidence'];
    });
    
    // Batasi hasil maksimal 50 rules
    $results = array_slice($results, 0, 50);
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    return [
        'success' => true,
        'results' => $results,
        'total_transactions' => $totalTransactions,
        'execution_time' => $executionTime,
        'rules_found' => count($results),
        'products_analyzed' => count($productIndex)
    ];
}

// Fungsi alternatif yang lebih sederhana
function runSimpleAssociation($csvFile) {
    $startTime = microtime(true);
    
    if (!file_exists($csvFile)) {
        return ['success' => false, 'message' => 'File tidak ditemukan'];
    }
    
    // Baca data
    $data = [];
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        fgetcsv($handle); // Skip header
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            $transId = $row[0];
            $product = trim($row[1]);
            
            if ($transId && $product) {
                if (!isset($data[$transId])) {
                    $data[$transId] = [];
                }
                if (!in_array($product, $data[$transId])) {
                    $data[$transId][] = $product;
                }
            }
        }
        fclose($handle);
    }
    
    if (count($data) < 2) {
        return ['success' => false, 'message' => 'Data kurang'];
    }
    
    // Analisis sederhana
    $rules = [];
    $transactions = array_values($data);
    
    foreach ($transactions as $items) {
        for ($i = 0; $i < count($items); $i++) {
            for ($j = 0; $j < count($items); $j++) {
                if ($i != $j) {
                    $a = $items[$i];
                    $b = $items[$j];
                    
                    $key = $a . '|' . $b;
                    if (!isset($rules[$key])) {
                        $rules[$key] = ['if' => $a, 'then' => $b, 'count' => 0];
                    }
                    $rules[$key]['count']++;
                }
            }
        }
    }
    
    // Hitung confidence
    $results = [];
    foreach ($rules as $rule) {
        $count_a = 0;
        foreach ($transactions as $items) {
            if (in_array($rule['if'], $items)) {
                $count_a++;
            }
        }
        
        if ($count_a > 0) {
            $confidence = $rule['count'] / $count_a;
            if ($confidence >= 0.3) {
                $results[] = [
                    'if_buy' => $rule['if'],
                    'then_buy' => $rule['then'],
                    'confidence' => round($confidence, 3),
                    'count' => $rule['count']
                ];
            }
        }
    }
    
    // Sort
    usort($results, function($x, $y) {
        return $y['confidence'] <=> $x['confidence'];
    });
    
    return [
        'success' => true,
        'results' => $results,
        'total_transactions' => count($data),
        'rules_found' => count($results)
    ];
}
?>