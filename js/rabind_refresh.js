async function refreshOnlineUsers(){

    try{

        const response = await fetch('ajax/online_users.php');
        const users = await response.json();

        const tableBody = document.getElementById("onlineUsersBody");

        if(!tableBody) return;

        tableBody.innerHTML = "";

        users.forEach(u => {

            const badge = u.online
                ? '<span class="badge text-bg-success">Online</span>'
                : '<span class="badge text-bg-secondary">Offline</span>';

            tableBody.innerHTML += `
            <tr>
                <td>
                    ${u.username}
                    ${badge}
                </td>

                <td>${u.ip || ''}</td>
                <td>${u.mac || ''}</td>

                <td>
                    <a class="btn btn-danger btn-sm"
                    href="user_disconnect.php?u=${encodeURIComponent(u.username)}">
                    Disconnect
                    </a>
                </td>
            </tr>
            `;
        });

    }catch(e){
        console.log("Refresh error");
    }
}

setInterval(refreshOnlineUsers, 60000);
refreshOnlineUsers();
