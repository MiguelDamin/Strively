<?php
// includes/calculos-treinador.php

function getAdesaoSemanal($pdo, $aluno_id, $dataReferencia = null) {
    if (!$dataReferencia) $dataReferencia = date('Y-m-d');
    
    $dataFim = $dataReferencia;
    $dataInicio = date('Y-m-d', strtotime("$dataFim -6 days"));
    
    $dataFimAnt = date('Y-m-d', strtotime("$dataInicio -1 day"));
    $dataInicioAnt = date('Y-m-d', strtotime("$dataFimAnt -6 days"));
    
    $stmt = $pdo->prepare("SELECT status, data_treino FROM treinos WHERE aluno_id = ? AND data_treino >= ? AND data_treino <= ?");
    
    $stmt->execute([$aluno_id, $dataInicio, $dataFim]);
    $treinosAtual = $stmt->fetchAll();
    
    $planejados = count($treinosAtual);
    $realizados = 0;
    foreach ($treinosAtual as $t) {
        if ($t['status'] === 'realizado') $realizados++;
    }
    
    $percentual = $planejados > 0 ? ($realizados / $planejados) * 100 : null;
    
    $stmt->execute([$aluno_id, $dataInicioAnt, $dataFimAnt]);
    $treinosAnt = $stmt->fetchAll();
    
    $planejadosAnt = count($treinosAnt);
    $realizadosAnt = 0;
    foreach ($treinosAnt as $t) {
        if ($t['status'] === 'realizado') $realizadosAnt++;
    }
    
    $percentualAnt = $planejadosAnt > 0 ? ($realizadosAnt / $planejadosAnt) * 100 : null;
    
    $variacao = null;
    if ($percentual !== null && $percentualAnt !== null) {
        $variacao = $percentual - $percentualAnt;
    }
    
    return [
        'realizados' => $realizados,
        'planejados' => $planejados,
        'percentual' => $percentual,
        'variacao_vs_semana_anterior' => $variacao
    ];
}

function getStreakAtual($pdo, $aluno_id) {
    $stmt = $pdo->prepare("SELECT data_treino, status FROM treinos WHERE aluno_id = ? AND data_treino <= CURRENT_DATE ORDER BY data_treino DESC");
    $stmt->execute([$aluno_id]);
    $treinos = $stmt->fetchAll();
    
    $diasTreinados = [];
    foreach ($treinos as $t) {
        if ($t['status'] === 'realizado') {
            $diasTreinados[$t['data_treino']] = true;
        }
    }
    
    $streak = 0;
    $hoje = date('Y-m-d');
    $ontem = date('Y-m-d', strtotime('-1 day'));
    
    $dataChecar = $hoje;
    if (!isset($diasTreinados[$hoje]) && isset($diasTreinados[$ontem])) {
        $dataChecar = $ontem;
    } elseif (!isset($diasTreinados[$hoje])) {
        return 0;
    }
    
    while (isset($diasTreinados[$dataChecar])) {
        $streak++;
        $dataChecar = date('Y-m-d', strtotime("$dataChecar -1 day"));
    }
    
    return $streak;
}

function getRpeMedio($pdo, $aluno_id, $dias = 14) {
    $dataInicio = date('Y-m-d', strtotime("-$dias days"));
    $stmt = $pdo->prepare("SELECT rpe FROM treinos WHERE aluno_id = ? AND status = 'realizado' AND rpe IS NOT NULL AND data_treino >= ?");
    $stmt->execute([$aluno_id, $dataInicio]);
    $rpes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($rpes)) return null;
    
    $soma = 0;
    foreach ($rpes as $r) {
        $soma += (float)$r;
    }
    return $soma / count($rpes);
}

function getProximaProva($pdo, $aluno_id) {
    $stmt = $pdo->prepare("
        SELECT ue.data_evento, e.nome AS evento_nome, ue.nome_manual
        FROM usuario_eventos ue
        LEFT JOIN eventos e ON e.id = ue.evento_id
        WHERE ue.usuario_id = ? AND ue.data_evento >= CURRENT_DATE
        ORDER BY ue.data_evento ASC
        LIMIT 1
    ");
    $stmt->execute([$aluno_id]);
    $res = $stmt->fetch();
    return $res ?: null;
}

function getVolumeSemanal6Semanas($pdo, $aluno_id) {
    $semanas = [];
    $hoje = new DateTime();
    $diaSemana = $hoje->format('N');
    $hoje->modify("-" . ($diaSemana - 1) . " days"); 
    
    for ($i = 5; $i >= 0; $i--) {
        $inicio = clone $hoje;
        $inicio->modify("-$i weeks");
        $fim = clone $inicio;
        $fim->modify("+6 days");
        
        $semanas[] = [
            'label'  => $inicio->format('d/m'),
            'inicio' => $inicio->format('Y-m-d'),
            'fim'    => $fim->format('Y-m-d'),
            'km_total' => 0
        ];
    }
    
    $dataFiltroInicio = $semanas[0]['inicio'];
    $dataFiltroFim    = $semanas[5]['fim'];
    
    $stmt = $pdo->prepare("SELECT data_treino, km_realizado_strava, distancia_planejada_km FROM treinos WHERE aluno_id = ? AND status = 'realizado' AND data_treino >= ? AND data_treino <= ?");
    $stmt->execute([$aluno_id, $dataFiltroInicio, $dataFiltroFim]);
    $treinos = $stmt->fetchAll();
    
    foreach ($treinos as $t) {
        $km = !empty($t['km_realizado_strava']) ? (float)$t['km_realizado_strava'] : (!empty($t['distancia_planejada_km']) ? (float)$t['distancia_planejada_km'] : 0);
        $dt = $t['data_treino'];
        
        foreach ($semanas as &$sem) {
            if ($dt >= $sem['inicio'] && $dt <= $sem['fim']) {
                $sem['km_total'] += $km;
                break;
            }
        }
    }
    
    return $semanas;
}

function isAlunoInativo($pdo, $aluno_id) {
    $dataInicio5 = date('Y-m-d', strtotime("-5 days"));
    $stmt = $pdo->prepare("SELECT status FROM treinos WHERE aluno_id = ? AND data_treino >= ? AND data_treino <= CURRENT_DATE");
    $stmt->execute([$aluno_id, $dataInicio5]);
    $treinos5 = $stmt->fetchAll();
    
    $temPlanejado = count($treinos5) > 0;
    $temRealizado = false;
    foreach ($treinos5 as $t) {
        if ($t['status'] === 'realizado') {
            $temRealizado = true;
            break;
        }
    }
    
    return ($temPlanejado && !$temRealizado);
}

function getAlertas($pdo, $aluno_id) {
    $alertas = [];
    
    if (isAlunoInativo($pdo, $aluno_id)) {
        $alertas[] = ['tipo' => 'Inatividade', 'severidade' => 'atencao', 'mensagem' => 'Nenhum treino realizado nos últimos 5 dias com plano ativo.'];
    }
    
    $rpe7 = getRpeMedio($pdo, $aluno_id, 7);
    if ($rpe7 !== null && $rpe7 >= 8) {
        $alertas[] = ['tipo' => 'RPE Alto', 'severidade' => 'atencao', 'mensagem' => 'RPE médio dos últimos 7 dias indica alta percepção de esforço (' . number_format($rpe7, 1) . '/10).'];
    }
    
    $dataHoje = date('Y-m-d');
    $semanas = getVolumeSemanal6Semanas($pdo, $aluno_id);
    $kmAt = $semanas[5]['km_total'];
    $kmAnt = $semanas[4]['km_total'];
    if ($kmAnt > 0) {
        $varKm = (($kmAt - $kmAnt) / $kmAnt) * 100;
        if ($varKm >= 30 && $kmAt > 10) { 
            $alertas[] = ['tipo' => 'Volume Alto', 'severidade' => 'info', 'mensagem' => 'Aumento de ' . round($varKm) . '% no volume semanal em relação à semana passada.'];
        }
    }
    
    $streak = getStreakAtual($pdo, $aluno_id);
    if ($streak >= 5) {
        $alertas[] = ['tipo' => 'Em Chamas', 'severidade' => 'positivo', 'mensagem' => 'Sequência de ' . $streak . ' dias seguidos de treino!'];
    }
    
    return $alertas;
}

function getUltimoTreinoRealizado($pdo, $aluno_id) {
    $stmt = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? AND status = 'realizado' ORDER BY data_treino DESC, id DESC LIMIT 1");
    $stmt->execute([$aluno_id]);
    return $stmt->fetch() ?: null;
}

function getUltimosRpes($pdo, $aluno_id, $limite = 6) {
    $stmt = $pdo->prepare("SELECT data_treino, titulo, rpe FROM treinos WHERE aluno_id = ? AND status = 'realizado' AND rpe IS NOT NULL ORDER BY data_treino DESC LIMIT " . (int)$limite);
    $stmt->execute([$aluno_id]);
    return $stmt->fetchAll();
}
