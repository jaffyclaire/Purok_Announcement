<?php if ($isAuthPage): ?>
        </div>
    </div>
        </div>
    </div>
<?php else: ?>
    </main>
</div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.location.hash === '#feedSearch') {
        const searchInput = document.querySelector('#feedSearch input[name="keyword"]');
        if (searchInput) searchInput.focus({ preventScroll: true });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>