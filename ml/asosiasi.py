import csv
import json
from collections import defaultdict

INPUT_CSV = "../dataset/transaksi.csv"
OUTPUT_JSON = "hasil_asosiasi.json"

transaksi = defaultdict(set)

with open(INPUT_CSV, newline='', encoding='utf-8') as f:
    reader = csv.DictReader(f)
    for row in reader:
        transaksi[row['id_transaksi']].add(row['nama_produk'])

rules = []

items = list(transaksi.values())

for t in items:
    for a in t:
        for b in t:
            if a != b:
                rules.append((a, b))

hasil = []
for a, b in set(rules):
    count_a = sum(1 for t in items if a in t)
    count_ab = sum(1 for t in items if a in t and b in t)

    confidence = round(count_ab / count_a, 2) if count_a else 0

    if confidence >= 0.5:
        hasil.append({
            "if_buy": a,
            "then_buy": b,
            "confidence": confidence
        })

with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
    json.dump(hasil, f, indent=4)

print("Analisis asosiasi selesai")
