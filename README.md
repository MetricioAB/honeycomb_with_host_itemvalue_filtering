
# Driftsättning - Honeycomb with HostItem Filter

---

## Förutsättningar

- Zabbix 7.0 (ej testad på senare versioner)
- Åtkomst till Zabbix-serverns filsystem
- Administratörsåtkomst i Zabbix webbgränssnitt

---

## 1. Installera modulen

Kopiera mappen `/honeycomb_with_host_item_filter/` till Zabbix modulkatalog.

Sökvägen kan variera beroende på installation. Standardplatsen för Zabbix moduler är `/usr/share/zabbix/modules/`.

Kontrollera att strukturen blir följande, utan en extra nästlad projektkatalog:

```text
<frontend>/modules/honeycomb_with_host_item_filter/
├── manifest.json
├── Widget.php
├── actions/
├── assets/
├── includes/
└── views/
```
---

## 2. Aktivera modulen i Zabbix

1. Logga in i Zabbix webbgränssnitt som administratör
2. Gå till **Administration > General > Modules**
3. Klicka **Scan directory**
4. Widgeten "Honecomb with HostItem filter " ska dyka upp i listan
5. Markera den och klicka **Enable**

Installationsflödet beskrivs också i [Zabbix dokumentation om frontendmoduler](https://www.zabbix.com/documentation/8.0/en/manual/extensions/frontendmodules). Välj dokumentationsversionen som motsvarar din installation.
---

## 3. Konfigurera widgeten i Zabbix

1. Välj hostar/hostgrupper och de **Item patterns** som ska visas som celler.
2. Välj ett itemnamn i **Host filter item patterns**. Namnet/mönstret används på varje host, så motsvarande items behöver ha samma namn eller matcha samma mönster.
3. Ange exempelvis `0` i **Hide host when value equals**.

Om något matchande filteritem på en host har det angivna senaste värdet döljs **alla** celler från den hosten. Filteritemet behöver inte ingå i de items eller itemtaggar som visas. Filtreringen utvärderas vid varje datauppdatering och sker före begränsningen av antalet celler.

- Tomt **Host filter item patterns** stänger av filtret, även för befintliga widgetar.
- Flera namn eller jokertecken kan matcha flera filteritems. En enda värdeträff räcker för att dölja hosten.
- Jämförelsen använder råvärdet, före value mapping och enhetsformatering. Numeriska items jämförs numeriskt (`0` matchar `0.0`); text/logg jämförs exakt och skiftlägeskänsligt.
- Endast aktiva filteritems med numeriska, text- eller loggvärden används.
- Saknas filteritem eller historik inom Zabbix inställda historikperiod för senaste värden behålls hosten.
