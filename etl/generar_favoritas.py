"""
Regenera favoritas.csv usando los track_ids reales de spotify_data.csv
y los DNIs reales de ListaUsuarios.csv.
Objetivo: ~2M filas (>1M requerido por el proyecto).
"""
import csv
import random
import os
import time

DATA_DIR   = os.path.join(os.path.dirname(__file__), "data")
SONGS_CSV  = os.path.join(DATA_DIR, "spotify_data.csv")
USERS_CSV  = os.path.join(DATA_DIR, "ListaUsuarios.csv")
OUTPUT_CSV = os.path.join(DATA_DIR, "favoritas.csv")

MIN_TRACKS_PER_USER = 5
MAX_TRACKS_PER_USER = 9
SEED = 42

def main():
    t0 = time.time()
    random.seed(SEED)

    print("Leyendo track_ids de spotify_data.csv...")
    track_ids = []
    with open(SONGS_CSV, encoding="utf-8") as f:
        reader = csv.reader(f)
        header = next(reader)
        # Columna track_id es la 4ª (índice 3) según la cabecera:
        # ,artist_name,track_name,track_id,...
        idx_track = header.index("track_id")
        for row in reader:
            if len(row) > idx_track and row[idx_track].strip():
                track_ids.append(row[idx_track].strip())
    track_ids = list(set(track_ids))  # únicos
    print(f"  {len(track_ids):,} track_ids únicos cargados.")

    print("Leyendo DNIs de ListaUsuarios.csv...")
    dnis = []
    with open(USERS_CSV, encoding="utf-8") as f:
        reader = csv.reader(f, delimiter=";")
        next(reader)  # cabecera
        for row in reader:
            if row and row[0].strip():
                dnis.append(row[0].strip())
    print(f"  {len(dnis):,} DNIs cargados.")

    print(f"Generando favoritas.csv ({MIN_TRACKS_PER_USER}-{MAX_TRACKS_PER_USER} canciones/usuario)...")
    total = 0
    with open(OUTPUT_CSV, "w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["dni", "track_id"])
        for dni in dnis:
            n = random.randint(MIN_TRACKS_PER_USER, MAX_TRACKS_PER_USER)
            sample = random.sample(track_ids, min(n, len(track_ids)))
            for tid in sample:
                writer.writerow([dni, tid])
                total += 1

    elapsed = time.time() - t0
    size_mb = os.path.getsize(OUTPUT_CSV) / 1024 / 1024
    print(f"\n✓ favoritas.csv generado: {total:,} filas | {size_mb:.1f} MB | {elapsed:.1f}s")

if __name__ == "__main__":
    main()
