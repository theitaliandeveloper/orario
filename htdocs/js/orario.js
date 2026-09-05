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
        if (VIEW_ID.contains("Sconosciuto")) {
            document.getElementById("page-title").innerText = "Errore nel caricamento";
            document.getElementById("desktop-table").innerHTML = "<a href=\"index.php\" class=\"btn btn-primary\">Torna alla home</a>";
            document.getElementById("mobile-view").innerHTML = "<a href=\"index.php\" class=\"btn btn-primary\">Torna alla home</a>";
            document.getElementById("pdf-export").innerHTML = "";
            return;
        }
        const res = await fetch(`api/getOrario.php?type=${VIEW_TYPE}&id=${encodeURIComponent(VIEW_ID)}`,{ signal: AbortSignal.timeout(3000) }); // Prova a caricare i dati con timeout di 2 secondi
        if (!res.ok) {
            if (res.status == 404) {
                location.replace("404.php");
            }
            else {
                document.getElementById("page-title").innerText = "Errore nel caricamento";
                document.getElementById("desktop-table").innerHTML = "<a href=\"index.php\" class=\"btn btn-primary\">Torna alla home</a>";
                document.getElementById("mobile-view").innerHTML = "<a href=\"index.php\" class=\"btn btn-primary\">Torna alla home</a>";
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

        function dayKey(day) {
            return day.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }

        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        const joinWithAnd = items => {
            if (items.length === 0) return "";
            if (items.length === 1) return items[0];
            if (items.length === 2) return `${items[0]} e ${items[1]}`;
            return `${items.slice(0, -1).join(", ")} e ${items[items.length - 1]}`;
        };


        function slotDetails(slot) {
            if (VIEW_TYPE === 'classe') {
                return {
                    secondary: joinWithAnd(slot.teachers || []),
                    rooms: joinWithAnd(slot.rooms || [])
                };
            }

            if (VIEW_TYPE === 'docente') {
                return {
                    secondary: joinWithAnd(slot.classes || []),
                    rooms: joinWithAnd(slot.rooms || [])
                };
            }

            return {
                secondary: joinWithAnd(
                    (slot.classes || [])
                        .map(item => item.teacher
                            ? `${item.class} (${item.teacher})`
                            : item.class
                        )
                ),
                rooms: ""
            };
        }

        // Render Desktop
        let dHead = `<th>Ora/Giorno</th>`;
        days.forEach(d => dHead += `<th>${d}</th>`);
        document.getElementById("desktop-head").innerHTML = dHead;

        let dBody = "";
        for (let i = 1; i <= 6; i++) {
            dBody += `<tr><td class="fw-bold">${hours[i-1]}</td>`;
            days.forEach(d => {
                const dayClean = dayKey(d);
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                if (slot && slot.subject) {
                    const details = slotDetails(slot);
                    const secondary = details.secondary;
                    const rooms = details.rooms;
                    
                    dBody += `<td data-label="${d}">
                        <div class="subject fw-bold text-primary-emphasis">${escapeHtml(slot.subject)}</div>
                        ${secondary ? `<div class="teacher small">${escapeHtml(secondary)}</div>` : ''}
                        ${rooms ? `<div class="room text-secondary-emphasis small">${escapeHtml(rooms)}</div>` : ''}
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
            const dayClean = dayKey(d);
            mBody += `<div class="card mb-3 shadow-sm"><div class="card-header fw-semibold">${escapeHtml(d)}</div><div class="list-group list-group-flush">`;
            for (let i = 1; i <= 6; i++) {
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                const hlabel = hours[i-1].replace("<br>", " ");
                if (slot && slot.subject) {
                    const details = slotDetails(slot);
                    const secondary = details.secondary;
                    const rooms = details.rooms;
                    
                    mBody += `<div class="list-group-item">
                        <div class="small text-body-secondary">${escapeHtml(hlabel)}</div>
                        <div class="fw-semibold text-primary-emphasis">${escapeHtml(slot.subject)}</div>
                        ${secondary ? `<div class="text-secondary-emphasis">${escapeHtml(secondary)}</div>` : ''}
                        ${rooms ? `<span class="badge border border-info text-info mt-1">${escapeHtml(rooms)}</span>` : ''}
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
        document.getElementById("desktop-table").innerHTML = "<a href=\"index.php\" class=\"btn btn-primary\">Torna alla home</a>";
        document.getElementById("mobile-view").innerHTML = "<a href=\"index.php\" class=\"btn btn-primary\">Torna alla home</a>";
        document.getElementById("pdf-export").innerHTML = "";
    }
});