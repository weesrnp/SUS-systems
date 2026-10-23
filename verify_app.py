# -*- coding: utf-8 -*-
from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def run_checks():
    r_home = client.get("/")
    assert r_home.status_code == 200
    html = r_home.text
    assert 'data-theme="light"' in html
    assert "anime.min.js" in html
    assert "echarts" not in html
    assert "Portaria 7.639" not in html
    assert 'rel="icon"' in html
    print("[OK] GET / -> Abre em Modo Claro por padrão, sem ECharts, sem Portaria 7.639 e com favicon")

    for icon in ("favicon.svg", "favicon.ico", "apple-touch-icon.png"):
        r_icon = client.get(f"/{icon}")
        assert r_icon.status_code == 200 and r_icon.content, icon
    assert client.get("/nao-existe.txt").status_code == 404
    print("[OK] GET /favicon.svg, /favicon.ico, /apple-touch-icon.png -> servidos na raiz")

    r_cat = client.get("/api/catalog")
    assert r_cat.status_code == 200
    areas = r_cat.json()["areas"]
    groups = {g["id"]: g for a in areas for g in a["groups"]}
    assert "VIG_EPIDEMIOLOGICA" not in groups
    assert "VIG_SANITARIA" not in groups
    sia_subs = [s["name"] for s in groups["SIA"]["subGroups"]]
    assert sia_subs == ["BPA", "SIA", "FPO", "RAAS", "Remessa"], sia_subs
    print("[OK] GET /api/catalog -> SIA sem APAC, na ordem:", sia_subs)

    assert client.get("/api/municipal/summary").status_code == 404
    print("[OK] GET /api/municipal/summary -> removido (Portaria 7.639)")

    r_health = client.get("/api/health")
    assert r_health.status_code == 200
    assert r_health.json()["status"] == "healthy"
    print("[OK] GET /api/health -> Status saudável e clusters conectados")

    r_health_alt = client.get("/health")
    assert r_health_alt.status_code == 200
    assert r_health_alt.json()["status"] == "healthy"
    print("[OK] GET /health (fallback sem prefixo) -> Status saudável")


if __name__ == "__main__":
    run_checks()
