#!/bin/sh

# Überprüfen, ob die .env Datei existiert
if [ ! -f .env ]; then
    # Kopiere die .env.example Datei zur .env Datei
    cp .env.example .env
    echo ".env Datei wurde von dem example erstellt, bitte den Inhalt anpassen."
fi
