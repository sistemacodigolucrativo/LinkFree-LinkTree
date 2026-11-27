#!/usr/bin/env python3
"""Check the published audit artifacts' internal consistency; no app or VPS access."""
from pathlib import Path
import re
import json
from collections import Counter

base = Path(__file__).resolve().parents[1] / "docs" / "homologacao-automatizada"
matrix = (base / "MATRIZ-RESULTADOS-43-TESTES.md").read_text(encoding="utf-8")
trace = (base / "MATRIZ-RASTREABILIDADE-43-TESTES.md").read_text(encoding="utf-8")
report = (base / "RELATORIO-TECNICO-COMPLETO.md").read_text(encoding="utf-8")
rows = re.findall(r"^\| (TC-\d\d-[A-Z]) \| [ABCD] \| (APROVADO|REPROVADO|PARCIAL|BLOQUEADO|NÃO AUTOMATIZÁVEL) \|$", matrix, re.M)
trace_rows = re.findall(r"^\| (TC-\d\d-[A-Z]) \| [ABCD] \| (APROVADO|REPROVADO|PARCIAL|BLOQUEADO|NÃO AUTOMATIZÁVEL) \|", trace, re.M)
assert len(rows) == 43 and len(set(k for k, _ in rows)) == 43, "Matrix must have 43 distinct IDs"
assert rows == trace_rows, "Traceability status differs from the original result matrix"
expected = {"APROVADO": 4, "REPROVADO": 2, "PARCIAL": 34, "BLOQUEADO": 2, "NÃO AUTOMATIZÁVEL": 1}
assert Counter(status for _, status in rows) == expected, "Result counts changed without re-execution"
assert "43 casos" in report and "4 aprovados" in report and "2 reprovados" in report
for filename in ("resultados.json", "evidencias/http.json", "evidencias/functional.json", "evidencias/browser.json"):
    data = json.loads((base / filename).read_text(encoding="utf-8"))
    assert data is not None
records = json.loads((base / "resultados.json").read_text(encoding="utf-8"))
assert {x["id"] for x in records} == {k for k, _ in rows}
assert len(records) == 43
for target in re.findall(r"\]\((evidencias/[^)]+)\)", trace):
    assert (base / target).is_file(), f"Missing evidence reference: {target}"
assert (base / "FALHAS-E-RISCOS.md").is_file()
assert (base / "PENDENCIAS-HOMOLOGACAO-MANUAL.md").is_file()
print("PASS audit: 43 IDs, historical status counts, JSON data, and evidence links.")
print("This is documentation consistency only; not homologation of production.")
