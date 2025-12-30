import sys, json, csv
print("Python ML Running...")
try:
    transactions = {}
    with open("dataset/transaksi.csv", "r") as f:
        reader = csv.DictReader(f)
        for row in reader:
            tid = row["id_transaksi"]
            product = row["nama_produk"].strip()
            if tid and product:
                if tid not in transactions:
                    transactions[tid] = []
                if product not in transactions[tid]:
                    transactions[tid].append(product)
    
    # Simple analysis
    results = []
    trans_list = list(transactions.values())
    
    for items in trans_list:
        for i in range(len(items)):
            for j in range(len(items)):
                if i != j:
                    a, b = items[i], items[j]
                    count_a = sum(1 for t in trans_list if a in t)
                    count_ab = sum(1 for t in trans_list if a in t and b in t)
                    
                    if count_a > 0:
                        confidence = count_ab / count_a
                        if confidence >= 0.3:
                            results.append({
                                "if_buy": a,
                                "then_buy": b,
                                "confidence": round(confidence, 3)
                            })
    
    # Remove duplicates
    unique = []
    seen = set()
    for r in results:
        key = (r["if_buy"], r["then_buy"])
        if key not in seen:
            seen.add(key)
            unique.append(r)
    
    # Sort
    unique.sort(key=lambda x: x["confidence"], reverse=True)
    
    # Save
    with open("ml/hasil_asosiasi.json", "w") as f:
        json.dump(unique[:50], f, indent=4)
    
    print(f"Success: {len(unique)} rules found")
    
except Exception as e:
    print(f"Error: {str(e)}")
    sys.exit(1)