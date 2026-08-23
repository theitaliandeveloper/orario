/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see https://www.gnu.org/licenses/.
*/

// JavaScript per la tabella oraria

document.addEventListener("DOMContentLoaded", async function() {
    const days = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
    const hours = [
        "Prima ora<br> 7:50 - 8:50",
        "Seconda ora<br> 8:50 - 9:45",
        "Terza ora<br> 9:55 - 10:50",
        "Quarta ora<br> 10:50 - 11:45",
        "Quinta ora<br> 11:55 - 12:50",
        "Sesta ora<br> 12:50 - 13:50"
    ];

    try {
        const res = await fetch(`api/getOrario.php?type=${VIEW_TYPE}&id=${encodeURIComponent(VIEW_ID)}`,{ signal: AbortSignal.timeout(2000) }); // Prova a caricare i dati con timeout di 2 secondi
        if (!res.ok) {
            if (res.status == 404) {
                location.replace("404.php");
            }
            else {
                document.getElementById("page-title").innerText = "Errore nel caricamento";
                document.getElementById("desktop-table").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
                document.getElementById("mobile-view").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
                document.getElementById("pdf-export").innerHTML = "";
            }
            return;
        }
        
        const data = await res.json();
        
        let titleName = "";
        if (VIEW_TYPE === "classe") titleName = data.class_name;
        if (VIEW_TYPE === "docente") titleName = data.teacher;
        if (VIEW_TYPE === "laboratorio") titleName = data.room;
        document.getElementById("page-title").innerText = `Orario ${VIEW_TYPE} ${titleName}`;
        document.title = `Orario ${VIEW_TYPE} ${titleName}`;

        const timetable = data.timetable;

        // Render Desktop
        let dHead = `<th>Ora/Giorno</th>`;
        days.forEach(d => dHead += `<th>${d}</th>`);
        document.getElementById("desktop-head").innerHTML = dHead;

        let dBody = "";
        for (let i = 1; i <= 6; i++) {
            dBody += `<tr><td class="fw-bold">${hours[i-1]}</td>`;
            days.forEach(d => {
                const dayClean = d.replace("ì", "i"); // getOrario cleans accents in JSON keys! Lunedi instead of Lunedì
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                if (slot && slot.subject) {
                    let secondary = "";
                    if (VIEW_TYPE === 'classe' && slot.teachers) secondary = slot.teachers.join(", ");
                    if (VIEW_TYPE === 'docente' && slot.classes) secondary = slot.classes.join(", ");
                    if (VIEW_TYPE === 'laboratorio' && slot.classes) secondary = slot.classes.map(c => `${c.class} (${c.teacher})`).join(", ");

                    let rooms = slot.rooms ? slot.rooms.join(", ") : "";
                    
                    dBody += `<td data-label="${d}">
                        <div class="subject fw-bold text-primary-emphasis">${slot.subject}</div>
                        ${secondary ? `<div class="teacher small">${secondary}</div>` : ''}
                        ${rooms ? `<div class="room text-secondary-emphasis small">${rooms}</div>` : ''}
                    </td>`;
                } else {
                    dBody += `<td data-label="${d}"></td>`;
                }
            });
            dBody += `</tr>`;
        }
        document.getElementById("desktop-body").innerHTML = dBody;

        // Render Mobile
        let mBody = "";
        days.forEach(d => {
            const dayClean = d.replace("ì", "i");
            mBody += `<div class="card mb-3 shadow-sm"><div class="card-header fw-semibold">${d}</div><div class="list-group list-group-flush">`;
            for (let i = 1; i <= 6; i++) {
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                const hlabel = hours[i-1].replace("<br>", " ");
                if (slot && slot.subject) {
                    let secondary = "";
                    if (VIEW_TYPE === 'classe' && slot.teachers) secondary = slot.teachers.join(", ");
                    if (VIEW_TYPE === 'docente' && slot.classes) secondary = slot.classes.join(", ");
                    if (VIEW_TYPE === 'laboratorio' && slot.classes) secondary = slot.classes.map(c => `${c.class} (${c.teacher})`).join(", ");

                    let rooms = slot.rooms ? slot.rooms.join(", ") : "";
                    
                    mBody += `<div class="list-group-item">
                        <div class="small text-body-secondary">${hlabel}</div>
                        <div class="fw-semibold text-primary-emphasis">${slot.subject}</div>
                        ${secondary ? `<div class="text-secondary-emphasis">${secondary}</div>` : ''}
                        ${rooms ? `<span class="badge border border-info text-info mt-1">${rooms}</span>` : ''}
                    </div>`;
                } else {
                    mBody += `<div class="list-group-item text-body-tertiary">
                        <div class="small">${hlabel}</div>
                        <div>—</div>
                    </div>`;
                }
            }
            mBody += `</div></div>`;
        });
        document.getElementById("mobile-view").innerHTML = mBody;
        
    } catch (e) {
        console.error(e);
        document.getElementById("page-title").innerText = "Errore nel caricamento";
        document.getElementById("desktop-table").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
        document.getElementById("mobile-view").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
        document.getElementById("pdf-export").innerHTML = "";
    }
});