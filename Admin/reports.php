<?php
$pageTitle = 'Reports';
require_once 'auth.php';

$byPurpose = $pdo->query("SELECT purpose, COUNT(*) as cnt FROM sit_in_records GROUP BY purpose ORDER BY cnt DESC")->fetchAll();
$byLab     = $pdo->query("SELECT lab, COUNT(*) as cnt FROM sit_in_records GROUP BY lab ORDER BY cnt DESC")->fetchAll();
$byDate    = $pdo->query("SELECT DATE(time_in) as day, COUNT(*) as cnt FROM sit_in_records GROUP BY DATE(time_in) ORDER BY day DESC LIMIT 14")->fetchAll();

include 'header.php';

// Mock AI Recommendation Logic
$topPurpose = $byPurpose[0]['purpose'] ?? 'General tasks';
$topLab = $byLab[0]['lab'] ?? 'any available lab';
$aiMessage = "Based on recent activity, <strong>$topPurpose</strong> is the most requested sit-in purpose. Consider expanding software applications and learning resources related to $topPurpose. Furthermore, <strong>Lab $topLab</strong> experiences the highest traffic; ensure hardware maintenance is prioritized for this room to minimize downtime.";
?>
<div class="page-content">
    <div class="page-title">Sit-in Reports & Analytics</div>
    
    <!-- AI Recommendation Section -->
    <div class="table-card" style="background: linear-gradient(135deg, #fdfbfb, #f3eefc); border-left: 5px solid #7B4BB8; margin-bottom: 24px;">
        <h3 style="color: #5B2D8E; margin-bottom: 10px;"><i class="fas fa-robot"></i> AI Insight & Recommendation</h3>
        <p style="font-size: 13.5px; color: #444; line-height: 1.6; margin: 0; padding: 5px 0;">
            <?= $aiMessage ?>
        </p>
    </div>

    <div class="two-col">
        <div class="table-card">
            <h3><i class="fas fa-chart-bar"></i> Sit-in by Purpose</h3>
            <canvas id="purposeBar" height="220"></canvas>
        </div>
        <div class="table-card">
            <h3><i class="fas fa-flask"></i> Sit-in by Lab</h3>
            <canvas id="labBar" height="220"></canvas>
        </div>
    </div>
    <div class="table-card">
        <h3><i class="fas fa-calendar"></i> Daily Sit-in (Last 14 Days)</h3>
        <canvas id="dailyLine" height="100"></canvas>
    </div>
</div>
<script>
const pLabels = <?=json_encode(array_column($byPurpose,'purpose'))?>;
const pData   = <?=json_encode(array_column($byPurpose,'cnt'))?>;
const lLabels = <?=json_encode(array_column($byLab,'lab'))?>;
const lData   = <?=json_encode(array_column($byLab,'cnt'))?>;
const dLabels = <?=json_encode(array_column($byDate,'day'))?>;
const dData   = <?=json_encode(array_column($byDate,'cnt'))?>;
const colors  = ['#5B2D8E','#7B4BB8','#F0B429','#C88F0A','#9c27b0','#ff9800'];

new Chart(document.getElementById('purposeBar'),{type:'bar',data:{labels:pLabels,datasets:[{label:'Count',data:pData,backgroundColor:colors}]},options:{plugins:{legend:{display:false}}}});
new Chart(document.getElementById('labBar'),{type:'bar',data:{labels:lLabels,datasets:[{label:'Count',data:lData,backgroundColor:'#7B4BB8'}]},options:{plugins:{legend:{display:false}}}});
new Chart(document.getElementById('dailyLine'),{type:'line',data:{labels:dLabels.reverse(),datasets:[{label:'Sit-ins',data:dData.reverse(),borderColor:'#5B2D8E',backgroundColor:'rgba(91,45,142,0.1)',fill:true,tension:0.4}]},options:{plugins:{legend:{display:false}}}});
</script>
</body></html>
