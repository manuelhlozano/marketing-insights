import os
import glob
import csv

UPLOAD_DIR = r"C:\Users\ADMINISTRATIVO\.gemini\antigravity-ide\brain\340f08d2-463e-4913-a5b6-c6f10f7bbad0\.user_uploaded"

csv_files = glob.glob(os.path.join(UPLOAD_DIR, "*.csv"))
print(f"Total CSV files found: {len(csv_files)}\n")

for f in sorted(csv_files):
    fname = os.path.basename(f)
    try:
        with open(f, 'r', encoding='utf-8', errors='ignore') as fp:
            reader = csv.reader(fp)
            rows = list(reader)
            print(f"=== File: {fname} (Rows: {len(rows)}) ===")
            if rows:
                print(f"Header: {rows[0]}")
                if len(rows) > 1:
                    print(f"Row 1: {rows[1]}")
                if len(rows) > 2:
                    print(f"Row 2: {rows[2]}")
            print("-" * 50)
    except Exception as e:
        print(f"Error reading {fname}: {e}")

