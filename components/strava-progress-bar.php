<?php
function getStravaProgressBarHtml($planejado, $realizado) {
    if (!$planejado || $planejado <= 0) return '';
    $kmReal = $realizado ? (float)$realizado : (float)$planejado;
    $pct = round(($kmReal / $planejado) * 100);
    $cor = '#EF5350';
    if ($pct >= 70) $cor = '#1DB954';
    elseif ($pct >= 60) $cor = '#FFA726';
    
    $fR = rtrim(rtrim(number_format($kmReal, 1, ',', '.'), '0'), ',');
    $fP = rtrim(rtrim(number_format($planejado, 1, ',', '.'), '0'), ',');
    
    $fillWidth = min($pct, 100);
    
    return '
    <div style="margin-top: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 0.75rem;">
            <span style="color: #333; font-weight: 600;">' . $fR . 'km de ' . $fP . 'km planejados</span>
            <span style="font-weight: 700; color: ' . $cor . ';">' . $pct . '%</span>
        </div>
        <div style="width: 100%; height: 6px; background-color: #e5e5e5; border-radius: 4px; overflow: hidden;">
            <div style="width: ' . $fillWidth . '%; height: 100%; background-color: ' . $cor . '; border-radius: 4px; transition: width 0.3s ease;"></div>
        </div>
    </div>';
}
?>
<script>
function getStravaProgressBarHtmlJS(planejado, realizado) {
    if (!planejado || parseFloat(planejado) <= 0) return '';
    const plan = parseFloat(planejado);
    let kmReal = parseFloat(realizado);
    if (isNaN(kmReal) || kmReal === 0) kmReal = plan;
    
    const pct = Math.round((kmReal / plan) * 100);
    let cor = '#EF5350';
    if (pct >= 70) cor = '#1DB954';
    else if (pct >= 60) cor = '#FFA726';
    
    const fR = kmReal.toFixed(1).replace('.0','').replace('.',',');
    const fP = plan.toFixed(1).replace('.0','').replace('.',',');
    const fillWidth = Math.min(pct, 100);
    
    return `
    <div style="margin-top: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 0.75rem;">
            <span style="color: #333; font-weight: 600;">${fR}km de ${fP}km planejados</span>
            <span style="font-weight: 700; color: ${cor};">${pct}%</span>
        </div>
        <div style="width: 100%; height: 6px; background-color: #e5e5e5; border-radius: 4px; overflow: hidden;">
            <div style="width: ${fillWidth}%; height: 100%; background-color: ${cor}; border-radius: 4px; transition: width 0.3s ease;"></div>
        </div>
    </div>`;
}
</script>
