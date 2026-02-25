async function showUserDetails(username){

    const res = await fetch('ajax/user_details.php?u=' + encodeURIComponent(username)
    );

    const data = await res.json();

    let html = "";

    /* Traffic */
    html += "<h6>📊 Traffico storico</h6>";
    html += "<ul class='list-group mb-3'>";

    data.traffic.forEach(t=>{
        const total = parseInt(t.acctinputoctets) +
                      parseInt(t.acctoutputoctets);

        html += `
        <li class="list-group-item">
        ${t.acctstarttime} -
        ${formatBytes(total)}
        </li>`;
    });

    html += "</ul>";


    /* Failed logins */
    html += "<h6 class='text-danger'>❌ Login falliti</h6>";

    html += "<ul class='list-group mb-3'>";
    data.failed.forEach(f=>{
        html += `
        <li class="list-group-item">
        ${f.authdate}
        </li>`;
    });
    html += "</ul>";


    /* MACs */
    html += "<h6>📶 MAC Devices</h6>";

    html += "<ul class='list-group'>";
    data.macs.forEach(m=>{
        html += `
        <li class="list-group-item">
        ${m.callingstationid}
        </li>`;
    });
    html += "</ul>";


    document.getElementById("userDetailsContent").innerHTML = html;

    new bootstrap.Modal(
        document.getElementById("userModal")
    ).show();
}