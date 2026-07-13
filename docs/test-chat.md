# Chat – manueller Testplan

Alle Szenarien beziehen sich auf eine Session mit aktiviertem BBB-Chat
(globale Einstellung **und** Session-Option „BBB-Chat integrieren" aktiviert).

---

## Vorbereitung

| Was | Wo |
|---|---|
| Globalen Chat aktivieren | Admin → BBB → Einstellungen → „BBB-Chat integrieren" |
| Test-Session anlegen | Space oder global, „BBB-Chat integrieren" ✓ |
| Mindestens zwei Nutzer bereit | z. B. Moderator (kann starten) + Teilnehmer |
| BBB-Webhooks aktiv | bbb-webhooks läuft und sendet Events an HumHub |

---

## 1 — Nachricht außerhalb des Meetings (Off-Meeting)

**Ziel:** Nachricht landet in der DB und wird in der Chat-Box angezeigt. Sie wird **nicht** in BBB injiziert.

1. Session-Detailseite öffnen – Meeting ist **nicht** gestartet.
2. Im Chat-Feld eine Nachricht eingeben, Senden-Button oder **Enter** drücken.
3. ✅ Nachricht erscheint sofort in der Chat-Box.
4. ✅ Chat-Header zeigt **kein** „Live"-Badge.
5. ✅ Moderatoren erhalten eine HumHub-Benachrichtigung (`ChatMsgReceived`).
6. DB-Check: `sent_at IS NULL`, `session_meeting_id IS NULL`, `source = 'humhub'`, `user_id` gesetzt.

---

## 2 — Meeting starten: System-Nachricht erscheint

**Ziel:** Beim Start erscheint eine Trennzeile „Meeting Start" – Off-Meeting-Nachrichten werden **nicht** in BBB injiziert.

1. ≥ 1 Off-Meeting-Nachricht wie in Szenario 1 schreiben.
2. Moderator startet das Meeting.
3. BBB-Webhook `meeting-created` (oder `meeting-started`) wird empfangen.
4. ✅ In der HumHub-Chat-Box erscheint eine Trennzeile **„Meeting Start · HH:MM"**.
5. ✅ Off-Meeting-Nachrichten bleiben in HumHub sichtbar, erscheinen aber **nicht** im BBB-Chat.
6. DB-Check: neuer Eintrag mit `source = 'system'`, `message = 'meeting-started'`, `session_meeting_id` gesetzt.

---

## 3 — Nachricht während des Meetings (Live)

**Ziel:** Nachricht wird sofort an BBB gesendet und in beiden Oberflächen sichtbar.

1. Meeting läuft – Chat-Header zeigt Badge **„Live"**.
2. Nachricht in HumHub eingeben und senden.
3. ✅ Feedback „Message sent." erscheint kurz.
4. ✅ Nachricht erscheint in der HumHub-Chat-Box.
5. ✅ Nachricht erscheint im BBB-Chat-Fenster (ggf. kurze Verzögerung).
6. ✅ Moderatoren erhalten eine `ChatMsgReceived`-Benachrichtigung.
7. DB-Check: `source = 'humhub'`, `sent_at` gesetzt, `session_meeting_id` gesetzt.

---

## 4 — Nachricht aus BBB kommt in HumHub an (Webhook)

**Ziel:** Im BBB-Meeting getippte Nachrichten erscheinen automatisch in HumHub.

1. Meeting läuft, HumHub-Seite offen lassen.
2. Im BBB-Browser-Fenster eine Nachricht in den BBB-Chat schreiben.
3. ✅ Spätestens nach **5 Sekunden** (Poll-Intervall) erscheint die Nachricht in HumHub.
4. ✅ Nachricht ist mit **„· BBB"** gekennzeichnet.
5. ✅ Moderatoren erhalten eine `ChatMsgReceived`-Benachrichtigung.
6. ✅ Avatar zeigt HumHub-Profilbild wenn Nutzer via HumHub-Join beigetreten ist, sonst Initial-Bubble.
7. DB-Check: `source = 'bbb'`, `user_id` gesetzt oder NULL (Gast/Extern).

### Echo-Dedup

8. Nachricht in HumHub eingeben → wird an BBB gesendet → BBB schickt `chat-group-message-sent` zurück.
9. ✅ Nachricht erscheint **nicht** doppelt in HumHub.

---

## 5 — Meeting beenden: System-Nachricht erscheint

**Ziel:** Beim Ende erscheint eine Trennzeile „Meeting End".

1. Laufendes Meeting beenden.
2. BBB-Webhook `meeting-ended` wird empfangen.
3. ✅ In der Chat-Box erscheint eine Trennzeile **„Meeting End · HH:MM"**.
4. DB-Check: neuer Eintrag mit `source = 'system'`, `message = 'meeting-ended'`, `session_meeting_id IS NULL`.

---

## 6 — Aufnahme: System-Nachrichten

**Ziel:** Aufnahme-Start und -Stop werden als Trennzeilen protokolliert.

1. Meeting läuft, Aufnahme starten.
2. ✅ Trennzeile **„● Recording started · HH:MM"** erscheint in der Chat-Box (rot).
3. Aufnahme stoppen.
4. ✅ Trennzeile **„■ Recording stopped · HH:MM"** erscheint (grau).
5. DB-Check: Einträge mit `source = 'system'`, `message = 'recording-started'` bzw. `'recording-stopped'`.

---

## 7 — Aufnahme fertig: Moderator-Benachrichtigung

**Ziel:** Wenn eine Aufnahme veröffentlicht ist, werden Moderatoren benachrichtigt.

1. BBB verarbeitet eine Aufnahme fertig (`rap-publish-ended`-Webhook).
2. ✅ Moderatoren erhalten eine `RecordingReady`-Benachrichtigung mit Sessionname.

---

## 8 — Mehrere Meetings / Chronik

**Ziel:** Meeting-Grenzen werden korrekt dargestellt.

1. Meeting starten → Nachrichten schreiben → Meeting beenden.
2. Neues Meeting starten → weitere Nachrichten schreiben.
3. ✅ Chronik: Off-Meeting-Nachrichten → **„Meeting Start"** → Meeting-Nachrichten → **„Meeting End"** → **„Meeting Start"** → …
4. ✅ Datumstrennzeile erscheint wenn Nachrichten über Mitternacht gehen.

---

## 9 — Recordings-Box

**Ziel:** Recordings werden unterhalb des Chats angezeigt, ohne die Chat-Höhe zu verquetschen.

| Fall | Erwartetes Ergebnis |
|---|---|
| Keine Recordings | Recordings-Box wird **nicht** angezeigt |
| 1–n Recordings | Box erscheint mit `max-height: 180px` und eigenem Scroll |
| Viele Recordings | Innerhalb der Box scrollbar, Chat-Höhe bleibt unverändert |

---

## 10 — Avatar-Anzeige

| Fall | Erwartetes Ergebnis |
|---|---|
| Nutzer hat Profilbild | Rundes Profilbild |
| Nutzer hat kein Profilbild | Farbiges Kreis mit Initiale (Farbe konsistent für denselben Namen) |
| Externer BBB-Gast (kein HumHub-Account) | Farbiges Kreis mit Initiale des Anzeige-Namens |

---

## 11 — Chat deaktiviert

| Konfiguration | Erwartetes Ergebnis |
|---|---|
| Global aus, Session an | Chat-Box wird **nicht** gerendert |
| Global an, Session aus | Chat-Box wird **nicht** gerendert |
| Beides an | Chat-Box erscheint |

---

## 12 — UX / Tastatur / Scroll

| Aktion | Erwartetes Ergebnis |
|---|---|
| **Enter** im Textfeld | Nachricht absenden |
| **Shift+Enter** im Textfeld | Zeilenumbruch, kein Senden |
| Senden-Button bei leerem Feld | Fehlermeldung „Please enter a message." |
| Seite laden (viele Nachrichten) | Chat scrollt automatisch ans Ende |
| PJAX-Navigation zur Session | Chat scrollt automatisch ans Ende |
| Nach Senden | Chat scrollt automatisch ans Ende |
| Tab wechseln und zurück | Polling wird pausiert und wieder aufgenommen |

---

## 13 — Leere Chat-Box

1. Session ohne bisherige Nachrichten öffnen.
2. ✅ Text „No messages yet. Write something before or during the meeting." erscheint.
