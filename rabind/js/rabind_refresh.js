/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
