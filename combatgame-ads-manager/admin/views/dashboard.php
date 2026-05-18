<div class="wrap cgam-dashboard">
  <h1>CombatGame Ads Manager PRO</h1>
  <div class="cgam-cards">
    <div class="card"><h3>Impressões Hoje</h3><strong><?php echo esc_html((string)$stats['impressions']); ?></strong></div>
    <div class="card"><h3>Cliques Hoje</h3><strong><?php echo esc_html((string)$stats['clicks']); ?></strong></div>
    <div class="card"><h3>CTR Médio</h3><strong><?php echo esc_html((string)$stats['ctr']); ?>%</strong></div>
    <div class="card"><h3>Campanhas Ativas</h3><strong><?php echo esc_html((string)$stats['active']); ?></strong></div>
    <div class="card"><h3>Campanhas Pausadas</h3><strong><?php echo esc_html((string)$stats['paused']); ?></strong></div>
  </div>
  <canvas id="cgam-chart" height="120"></canvas>
</div>
