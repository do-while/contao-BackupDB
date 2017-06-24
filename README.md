# contao-BackupDB
<strong>Sicherung der Contao-Datenbank, automatisches Backup, Erstellung von Website-Templates für das Install Tool</strong>

Dieses Modul erweitert den Systembereich im Backend.

Mit einem Mausklick kann auf einfache Weise die komplette Datenbank gesichert werden. Die Backupdatei erhält man als Download um sie außerhalb des Webservers sichern zu können, z.B. auf dem eigenen PC.

Zusätzlich können auch Website-Templates für den Install Tool gespeichert werden. Diese Website-Templates können im InstallTool geladen werden.

Mit einem Server-Cronjob ist es möglich regelmäßige Backups der Datenbank ausführen und auf dem Webspace zu speichern. Bei größeren Datenbanken kann es passieren, dass die maximale Scriptlaufzeit überschritten wird, dann kann AutoBackupDB leider nicht verwendet werden.

Mit der Blacklist kann verhindert werden, Daten gespeichert werden, die nicht unbedingt bei einer Wiederherstellung benötigt werden. Beispielsweise sind die Tabellen tl_lock, tl_log, tl_search, tl_search_index, tl_session, tl_undo und tl_version für den Notfall nicht relevant. Beispielsweise kann durch Auslassen des Suchindexes die Scriptlaufzeit ausreichend sein.
Das AutoBackup sendet auf Wunsch nach erfolgreichem Backup eine Mail an den Systemadministrator, diese Mail kann die Backupdatei enthalten. Zusätzlich besteht die Möglichkeit, die Backupdatei zu komprimieren.

ACHTUNG!
Die Website-Templates eignen sich NICHT zur Übertragung einer Datensicherung auf ein anderes Contao-Release, da sich dabei in der Regel die Datenbank-Struktur ändert.

<br>
Version:<br>
4.4.0 stable - Version für Contao ab Version 4.4 LTS

