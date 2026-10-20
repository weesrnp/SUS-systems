# -*- coding: utf-8 -*-
from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def run_checks():
    r_home = client.get("/")
    assert r_home.status_code == 200
    html = r_home.text
    assert 'data-theme="dark"' in html
    assert "anime.min.js" in html
    assert "echarts.min.js" in html
    assert "Explorer & Diff" not in html
    assert "Explorer &amp; Diff" not in html
    print("[OK] GET / -> Abre em Modo Escuro por padrão (data-theme='dark'), Anime.js + ECharts integrados, sem 'Explorer & Diff'")

    r_cat = client.get("/api/catalog")
    assert r_cat.status_code == 200
    areas = r_cat.json()["areas"]
    all_group_ids = [g["id"] for a in areas for g in a["groups"]]
    all_group_names = [g["name"] for a in areas for g in a["groups"]]
    assert "VIG_EPIDEMIOLOGICA" not in all_group_ids
    assert "VIG_SANITARIA" not in all_group_ids
    print("[OK] GET /api/catalog -> Vigilâncias Epidemiológica e Sanitária removidas. Menus alinhados:", all_group_names)

if __name__ == "__main__":
    run_checks()
