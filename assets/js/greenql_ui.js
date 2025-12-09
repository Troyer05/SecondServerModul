document.querySelectorAll(".db-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        const db = btn.dataset.db;

        fetch("api.php?mode=tables&db=" + db)
            .then(r => r.text())
            .then(html => {
                document.getElementById("dynamic").innerHTML = html;
            });
    });
});

// Neue DB Form öffnen
document.getElementById("openCreateDb").addEventListener("click", () => {
    fetch("api.php?mode=createDbForm")
        .then(r => r.text())
        .then(html => {
            document.getElementById("dynamic").innerHTML = html;
        });
});
