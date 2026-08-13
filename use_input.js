document.addEventListener("DOMContentLoaded", function() {
get_stations();

const input_field = document.getElementById("station_input");
const out = document.getElementById("out");
const next = document.getElementById("next_round");
const suggest_div = document.getElementById("suggest");

input_field.addEventListener("input", suggest);
suggest_div.addEventListener("mousedown", use_input)
next.addEventListener("mousedown", reset_html)

let html_content = "";
async function use_input(event) {
    let station = event.target.textContent.replace(" " + count_only_letters(event.target.textContent), "");
    input_field.value = "";
    suggest_div.innerHTML = "";
    try {
        const resp = await fetch("game_logic.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({Station: station})
        });

        if (!resp.ok) {
            throw new Error(`Netzwerkfehler: ${resp.status}`);
        }

        const data = await resp.json();
        if (data.Error) {
            html_content += "<p> FEHLER! </p>"
        } else {
            if (data.direction != "") {
                html_content += "<div id=guess><p> Die gesuchte Station liegt im <b>" + data.direction + "</b> von <i><b>" + data.guess + "</b> " + count_only_letters(data.guess) + "</i> </p>";
            } else {
                html_content += "<div id=guess><p> Die gesuchte Station wurde gefunden: <i><b>" + data.guess + "</b> " + count_only_letters(data.guess) + "</i> </p>";
                next.innerHTML = "<p id=suggestion>Nächste Runde</p>";
                input_field.setAttribute("disabled", "disabled");
            }
            html_content += "<p id=" + data.correct_lines + ">" + data.lines + "</p>";
            html_content += "<p id=" + data.correct_district + ">" + data.district + "</p>";
            html_content += "<p id='" + data.correct_len + "'> Anzahl Buchstaben: <i>" + count_only_letters(data.guess) + "</i></p></div>";

        }
    } catch(e) {
        html_content = "<p>" + e + "</p>";
    }

    out.innerHTML = html_content;
}

let stationsliste = [];
async function get_stations() {
    try {
        const resp = await fetch("fetch_database.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({Station: "All"})
        });

        if (!resp.ok) {
            throw new Error(`Netzwerkfehler: ${resp.status}`);
        }

        const data = await resp.json();
        stationsliste = data;

    } catch (e) {}
}

let html_suggest = "";
function suggest(event) {
    html_suggest = "";
    let limit = 3;
    for (let i = 0; i < stationsliste.length; i++) {
        if (stationsliste[i][0].toLowerCase().startsWith(event.target.value.toLowerCase()) && limit > 0 && event.target.value != "") {
            html_suggest += "<p id='suggestion'>" + stationsliste[i][0] + " <i>" + count_only_letters(stationsliste[i][0]) + "</i></p>";
            limit -= 1;
        }
    }
    suggest_div.innerHTML = html_suggest;
}

function count_only_letters(text) {
    let letter_text = text.replaceAll(new RegExp("[^A-Za-zäöüÄÖÜß]", "g"), "");
    return "(" + letter_text.length + ")"
}

function reset_html() {
    html_content = "";
    out.innerHTML = "";
    suggest_div.innerHTML = "";
    next.innerHTML = "";
    input_field.removeAttribute("disabled");
}
});