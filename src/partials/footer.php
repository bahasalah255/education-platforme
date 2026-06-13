<?php $year = date('Y'); ?>
<footer class="site-footer" role="contentinfo">
  <div class="rd-footer-inner">
    <div style="display:flex;align-items:center;gap:10px">
      <div class="rd-footer-logo" style="font-size: 16px;">🏫</div>
      <div>
        <div class="rd-footer-name">Ma maison est une école</div>
        <div class="rd-footer-copy">Plateforme d'enseignement primaire en classe inversée</div>
      </div>
    </div>
    <div style="display: flex; gap: 16px; font-size: 13px;">
      <a href="/about.php" style="color: rgba(255,255,255,0.6); text-decoration: none;">À Propos</a>
      <a href="/lesson.php" style="color: rgba(255,255,255,0.6); text-decoration: none;">Leçons</a>
      <a href="/leaderboard.php" style="color: rgba(255,255,255,0.6); text-decoration: none;">Classement</a>
    </div>
    <div class="rd-footer-copy">&copy; <?= $year ?> — Tous droits réservés. Bonne chance ! 🎓</div>
  </div>
</footer>
