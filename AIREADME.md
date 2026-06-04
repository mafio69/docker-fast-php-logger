# AI Guidelines — docker-fast-logger
Pracujemy z AI codziennie. Te zasady.
_Warto je dodać do wytycznych dla AI_
I Prostota przede wszystkim
Minimum kod. Nie spekuluj. Brak funkcji poza żądaniem, brak abstrakcji dla kodu jednorazowego, brak "elastyczności" która nie była proszona, jeśli 200 → 50 linii, przepisz.
Test: Czy senior powiedziałby "skomplikowane"? Uprości.
II Chirurgiczne zmiany
Dotykaj co musisz. Nie ulepszaj sąsiedni kod. Nie refaktoruj niepsutego, pasuj do stylu, martwego kodu: wspomni, nie usuwaj, usuń tylko swoje orphans.
Test: Każda zmiana → bezpośrednio do wymagania.
III Wykonanie sterowane celem
Zdefiniuj sukces. Pętluj aż działa.

Zamiast "Dodaj walidację" → zmień na "Testy invalid → pass"
Zamiast "Napraw błąd" → zmień na "Test reprodukuje → pass"
Zamiast "Refaktoruj X" → zmień na "Testy przed i po: pass"

Wieloetapowe: plan + weryfikacja:
1. [Krok] → verify: [check]
2. [Krok] → verify: [check]
3. [Krok] → verify: [check]

# Pryncypia
KOD musi działać przed wysłaniem `push`  
KOD musi byc zgodny z zasadami programowania SOLID, DRY, cienkich kontrolerów i pojedyńczej odpowiedzielaności, dodatkowo obsługa błędów, `user` nie ma widzieć nieprzechwyconego błędu. Naprawiamy tam, gdzie jesteśmy
`hard code` np.: 8080 nie ma tego, ZAKAZ `hard code`, gdzie dotykamy- widzisz `hard code` pytasz! czy naprawiać zawsze!
Mój język to polski w nim rozmawiamy. Jednak w kodzie tylko angielski, polski to nasz prywatny język na zewnątrz tylko angielski.

### Environment Variables Priority
- `.env` - base configuration
- `.env.local` - local overrides (most sensitive)
- `.env.dev` - development environment
