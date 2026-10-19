        </div><!-- /.content-body -->
    </div><!-- /.main-wrapper -->
</div><!-- /.app-container -->

<script>
// Real-time clock in topbar
function updateClock() {
    const el = document.getElementById('topbar-clock');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
            + ' · ' + now.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
    }
}
setInterval(updateClock, 1000);
updateClock();
</script>
</body>
</html>
