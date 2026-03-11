/**
 * Converte i secondi in un formato leggibile (es: 1d 04h 20m)
 * @param {number} seconds - Durata in secondi
 */
function formatDuration(seconds) {
    if (!seconds || seconds <= 0) return "0s";

    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;

    const parts = [];
    if (d > 0) parts.push(`${d}d`);
    if (h > 0) parts.push(`${h.toString().padStart(2, '0')}h`);
    if (m > 0) parts.push(`${m.toString().padStart(2, '0')}m`);
    if (s > 0 && d === 0) parts.push(`${s.toString().padStart(2, '0')}s`); // Mostra i secondi solo se < 1 giorno

    return parts.join(' ');
}

/**
 * Formatta i byte in una stringa leggibile (KB, MB, GB, etc.)
 * @param {number} bytes - Il numero di byte da formattare
 * @param {number} decimals - Numero di decimali da mostrare (default 2)
 */
function formatBytes(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

    // Calcola l'indice dell'unità di misura
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}


async function showUserDetails(username) {
    const res = await fetch('ajax/user_details.php?u=' + encodeURIComponent(username));
    const data = await res.json();

    let html = "";

    /* 1. CALCOLO TOTALI TRAFFICO */
    let totalInput = 0;
    let totalOutput = 0;

    data.traffic.forEach(t => {
        totalInput += parseInt(t.acctinputoctets || 0);
        totalOutput += parseInt(t.acctoutputoctets || 0);
    });

    const totalCombined = totalInput + totalOutput;

    /* 2. RIEPILOGO GRAFICO (BOX SINTETICI) */
    html += "<h6>📊 Riepilogo Traffico Storico</h6>";
    html += `
    <div class="row g-2 mb-4">
        <div class="col-4">
            <div class="p-2 border rounded bg-light text-center">
                <small class="text-muted d-block text-uppercase" style="font-size:0.65rem">Download</small>
                <span class="fw-bold text-dark">${formatBytes(totalInput)}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded bg-light text-center">
                <small class="text-muted d-block text-uppercase" style="font-size:0.65rem">Upload</small>
                <span class="fw-bold text-dark">${formatBytes(totalOutput)}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded bg-primary text-white text-center shadow-sm">
                <small class="d-block text-uppercase" style="font-size:0.65rem">Totale Complessivo</small>
                <span class="fw-bold">${formatBytes(totalCombined)}</span>
            </div>
        </div>
    </div>`;

    /* 3. MAC DEVICES & FAILED LOGINS (COMPATTI) */
    html += "<div class='row mb-4'>";

    // Colonna MAC
    html += "<div class='col-md-6'>";
    html += "<h6>📶 MAC Registrati</h6>";
    if(data.macs.length > 0) {
        html += "<ul class='list-group list-group-flush border rounded'>";
        data.macs.forEach(m => {
            html += `<li class="list-group-item p-1 px-2 small"><code class="text-primary">${m.callingstationid}</code></li>`;
        });
        html += "</ul>";
    } else { html += "<p class='small text-muted'>Nessun dispositivo rilevato</p>"; }
    html += "</div>";

    // Colonna Login Falliti
    html += "<div class='col-md-6'>";
    html += "<h6>❌ Login falliti (ultimi)</h6>";
    if(data.failed.length > 0) {
        html += "<ul class='list-group list-group-flush border rounded'>";
        data.failed.slice(0, 5).forEach(f => { // Mostriamo solo gli ultimi 5 per brevità
            html += `<li class="list-group-item p-1 px-2 small text-danger">${f.authdate}</li>`;
        });
        html += "</ul>";
    } else { html += "<p class='small text-muted text-success'>Nessun fallimento</p>"; }
    html += "</div>";

    html += "</div>";

    /* 4. TABELLA SESSIONI (STORICO DETTAGLIATO) */
    html += "<h6>🕒 Ultime Sessioni</h6>";
    html += "<div class='table-responsive'>"; // Evita che la tabella rompa il layout su mobile
    html += "<table class='table table-sm table-striped border' style='font-size: 0.85rem;'>";
    html += `
    <thead class="table-light">
        <tr>
            <th>Inizio</th>
            <th>Fine</th>
            <th>Durata</th>
            <th>Down</th>
            <th>Up</th>
            <th>IP</th>
        </tr>
    </thead>
    <tbody>`;

    data.sessions.slice(0, 10).forEach(s => { // Limitiamo alle ultime 10 sessioni
        const download = parseInt(s.acctinputoctets) || 0;
        const upload = parseInt(s.acctoutputoctets) || 0;

        html += `
        <tr>
            <td class="text-nowrap">${s.acctstarttime ?? ''}</td>
            <td class="text-nowrap">${s.acctstoptime ?? 'Online'}</td>
            <td>${formatDuration(s.acctsessiontime)}</td>
            <td>${formatBytes(download)}</td>
            <td>${formatBytes(upload)}</td>
            <td><code>${s.framedipaddress ?? ''}</code></td>
        </tr>`;
    });

    html += "</tbody></table></div>";

    // Aggiorna il contenuto e mostra il modal
    document.getElementById("userDetailsContent").innerHTML = html;
    new bootstrap.Modal(document.getElementById("userModal")).show();
}
