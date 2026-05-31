#!/bin/bash
# Skanuj pliki przygotowane do commita (--staged)

# Sprawdź czy gitleaks jest zainstalowany
if ! command -v gitleaks &> /dev/null; then
    echo "⚠️  gitleaks nie jest zainstalowany. Pomijam skanowanie gitleaks."
    echo "Instalacja: https://github.com/gitleaks/gitleaks"
    exit 0
fi

gitleaks protect --staged --verbose

# Jeśli gitleaks znajdzie wyciek, zwróci kod różny od 0 i zablokuje commit
if [ $? -ne 0 ]; then
 echo "❌ Git commit zablokowany: Wykryto wrażliwe dane!"
 exit 1
fi