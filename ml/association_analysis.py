#!/usr/bin/env python3
# ml/association_analysis.py

import csv
import json
import sys
import os
from collections import defaultdict
from datetime import datetime
import traceback

def main():
    try:
        # Konfigurasi path
        BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
        INPUT_CSV = os.path.join(BASE_DIR, 'dataset', 'transaksi.csv')
        OUTPUT_JSON = os.path.join(BASE_DIR, 'ml', 'hasil_asosiasi.json')
        
        print("🚀 Memulai analisis asosiasi...")
        print(f"📁 Working directory: {os.getcwd()}")
        print(f"📁 Script location: {__file__}")
        print(f"📁 Input CSV: {INPUT_CSV}")
        print(f"📁 Output JSON: {OUTPUT_JSON}")
        
        # Cek file CSV
        if not os.path.exists(INPUT_CSV):
            print(f"❌ File CSV tidak ditemukan: {INPUT_CSV}")
            print(f"📁 Directory content: {os.listdir(os.path.dirname(INPUT_CSV))}")
            sys.exit(1)
        
        # Cek size file
        file_size = os.path.getsize(INPUT_CSV)
        print(f"📊 Ukuran file CSV: {file_size} bytes")
        
        # Baca data transaksi
        transaksi = defaultdict(set)
        product_counts = defaultdict(int)
        transaction_ids = set()
        
        with open(INPUT_CSV, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            
            # Cek header
            print(f"📋 Header CSV: {reader.fieldnames}")
            
            row_count = 0
            for row in reader:
                row_count += 1
                transaction_id = row.get('id_transaksi', '').strip()
                product_name = row.get('nama_produk', '').strip()
                
                if transaction_id and product_name:  # Pastikan data valid
                    transaksi[transaction_id].add(product_name)
                    product_counts[product_name] += 1
                    transaction_ids.add(transaction_id)
                
                # Log setiap 100 baris
                if row_count % 100 == 0:
                    print(f"   ✓ Diproses {row_count} baris...")
        
        transaction_count = len(transaksi)
        print(f"\n✅ Data berhasil dibaca:")
        print(f"   - Total baris: {row_count}")
        print(f"   - Total transaksi unik: {transaction_count}")
        print(f"   - Total produk unik: {len(product_counts)}")
        
        if transaction_count == 0:
            print("⚠️  Tidak ada transaksi yang valid ditemukan")
            # Simpan hasil kosong
            with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
                json.dump([], f, indent=4)
            print("✅ File JSON kosong disimpan")
            sys.exit(0)
        
        # Tampilkan sample transaksi
        print(f"\n📝 Sample transaksi:")
        for i, (tid, products) in enumerate(list(transaksi.items())[:3]):
            print(f"   {i+1}. Transaksi {tid}: {', '.join(products)}")
        
        # Proses analisis asosiasi
        print("\n🔍 Memproses analisis asosiasi...")
        
        # Konversi ke list untuk pemrosesan
        items = list(transaksi.values())
        total_transactions = len(items)
        
        # Generate semua kemungkinan pasangan dalam transaksi yang sama
        rules = []
        for transaction in items:
            products = list(transaction)
            for i in range(len(products)):
                for j in range(len(products)):
                    if i != j:
                        rules.append((products[i], products[j]))
        
        # Hapus duplikat
        unique_rules = list(set(rules))
        
        print(f"📊 Mengolah {len(unique_rules)} aturan potensial...")
        
        # Hitung metrik
        hasil = []
        min_confidence = 0.3  # Minimum confidence 30%
        min_support = 0.05    # Minimum support 5% (dikurangi)
        
        for antecedent, consequent in unique_rules:
            # Hitung support: P(A ∩ B)
            count_ab = sum(1 for t in items if antecedent in t and consequent in t)
            support = count_ab / total_transactions if total_transactions > 0 else 0
            
            # Hitung confidence: P(B|A) = P(A ∩ B) / P(A)
            count_a = sum(1 for t in items if antecedent in t)
            confidence = count_ab / count_a if count_a > 0 else 0
            
            # Filter berdasarkan threshold (lebih longgar)
            if confidence >= min_confidence and support >= min_support:
                hasil.append({
                    "if_buy": antecedent,
                    "then_buy": consequent,
                    "confidence": round(confidence, 3),
                    "support": round(support, 3),
                    "count_ab": count_ab,
                    "count_a": count_a
                })
        
        # Urutkan berdasarkan confidence tertinggi
        hasil.sort(key=lambda x: x['confidence'], reverse=True)
        
        # Batasi maksimal 50 aturan
        hasil = hasil[:50]
        
        # Simpan hasil
        with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
            json.dump(hasil, f, indent=4, ensure_ascii=False)
        
        print(f"\n✅ Analisis selesai!")
        print(f"   - Total aturan ditemukan: {len(hasil)}")
        print(f"   - Confidence min: {min_confidence * 100}%")
        print(f"   - Support min: {min_support * 100}%")
        
        if len(hasil) > 0:
            print(f"\n🏆 Top 3 Rekomendasi:")
            for i, rule in enumerate(hasil[:3], 1):
                print(f"   {i}. Jika beli '{rule['if_buy']}' → maka beli '{rule['then_buy']}'")
                print(f"      Confidence: {rule['confidence'] * 100:.1f}%, Support: {rule['support'] * 100:.1f}%")
                print(f"      Support count: {rule['count_ab']}/{total_transactions}")
        
        print(f"\n📁 Hasil disimpan di: {OUTPUT_JSON}")
        print(f"✨ Proses selesai dengan sukses!")
        
    except Exception as e:
        print(f"❌ ERROR: {str(e)}")
        print(f"📋 Traceback:")
        traceback.print_exc()
        sys.exit(1)

if __name__ == "__main__":
    main()