# Honeycomb with HostItem Filter

## Filtrera bort hostar via itemvärde

1. Välj hostar och de **Item patterns** som ska visas som celler.
2. Välj ett itemnamn i **Host filter item patterns**. Namnet/mönstret används på varje host, så motsvarande items behöver ha samma namn eller matcha samma mönster.
3. Ange exempelvis `0` i **Hide host when value equals**.

Om något matchande filteritem på en host har det angivna senaste värdet döljs **alla** celler från den hosten. Filteritemet behöver inte ingå i de items eller itemtaggar som visas. Filtreringen utvärderas vid varje datauppdatering och sker före begränsningen av antalet celler.

- Tomt **Host filter item patterns** stänger av filtret, även för befintliga widgetar.
- Flera namn eller jokertecken kan matcha flera filteritems. En enda värdeträff räcker för att dölja hosten.
- Jämförelsen använder råvärdet, före value mapping och enhetsformatering. Numeriska items jämförs numeriskt (`0` matchar `0.0`); text/logg jämförs exakt och skiftlägeskänsligt.
- Endast aktiva filteritems med numeriska, text- eller loggvärden används.
- Saknas filteritem eller historik inom Zabbix inställda historikperiod för senaste värden behålls hosten.

## Kontroll

Kör `php tests/host_filter.php` för isolerade regressionstester av hostfiltret. Testet använder simulerade API- och historiksvar; det ersätter inte ett integrationstest i Zabbix.

Kontrollera även i Zabbix med två hostar som har samma filteritem: värde `0` på den ena och `1` på den andra. Endast den andra hostens celler ska visas. Ändra värdet och invänta widgetens uppdatering för att kontrollera att hosten återkommer.
