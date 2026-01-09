<!-- /**
* Informação de versão e autoria
*
* Criação:
* Autor: 3S Jhonatan
* Versão inicial: 1.0
*
* Histórico de atualizações:
* Atualizado por: 3S Maclean
* Versão atual: 1.1
* Descrição da atualização: Atualização de versão e ajustes.
*
* @author 3S Jhonatan
* @updatedBy 3S Maclean
* @version 1.1
*/ -->
<?php // Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "Makuxi@23ka";
$dbname = "db_etic_contratos";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    $connection_error = "Falha na conexão: " . $conn->connect_error;
    echo $connection_status = "error";
} else {
    $connection_status = "connected";
    $conn->set_charset("utf8");
} ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ETIC - Contratos</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="css/font-poppins.css">
    <link rel="stylesheet" href="css/main.css">
</head>

<body>
    <?php
    // Obter parâmetros de filtro
    $mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : 'todos';
    $empresa_filtro = isset($_GET['empresa']) ? $_GET['empresa'] : 'todos';

    // Valores médios por empresa (dados fornecidos)
    $valores_medios = [
        'AMAZONCOPY' => 5042.00,
        'FEDERAL TELECOM' => 2796.00,
        'LEV TELECOM' => 677.00,
        'INFORR' => 117.00,
        'STARLINK' => 5304.00
    ];

    // Query base
    $sql = "SELECT 
                contrato,
                objeto,
                empresa,
                DATE_FORMAT(vencimento_contrato, '%d/%m/%Y') as vencimento_formatado,
                valor_medio_mensal,
                ultima_fatura,
                valor_anual,
                valor_empenhado,
                jan_25, fev_25, mar_25, abr_25, mai_25, jun_25,
                jul_25, ago_25, set_25, out_25, nov_25, dez_25,
                vencimento_contrato as vencimento_original
            FROM contratos_etic";

    // Aplicar filtros
    $where_conditions = [];

    if ($empresa_filtro != 'todos') {
        $where_conditions[] = "empresa = '" . $conn->real_escape_string($empresa_filtro) . "'";
    }

    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }

    $sql .= " ORDER BY vencimento_contrato ASC";

    // Executar a query
    if ($connection_status == "connected") {
        $result = $conn->query($sql);

        // Inicializar arrays e variáveis
        $contratos = array();
        $empresas = array();
        $faturas_ultrapassaram = array();
        $dados_grafico = array();
        $total_valor_anual = 0;
        $total_valor_empenhado = 0;
        $total_ultima_fatura = 0;
        $total_contratos = 0;
        $proximos_vencimentos = 0;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $contratos[] = $row;

                // Coletar empresas únicas
                if (!in_array($row['empresa'], $empresas)) {
                    $empresas[] = $row['empresa'];
                }

                // Calcular totais
                $total_valor_anual += floatval($row['valor_anual']);
                $total_valor_empenhado += floatval($row['valor_empenhado']);
                $total_ultima_fatura += floatval($row['ultima_fatura']);
                $total_contratos++;

                // Verificar vencimentos próximos (menos de 90 dias)
                if (!empty($row['vencimento_original'])) {
                    $vencimento = new DateTime($row['vencimento_original']);
                    $hoje = new DateTime();
                    $diferenca = $hoje->diff($vencimento);
                    $dias_para_vencer = $diferenca->days;

                    if ($diferenca->invert == 0 && $dias_para_vencer < 90) {
                        $proximos_vencimentos++;
                    }
                }

                // Verificar se última fatura ultrapassou valor médio
                $empresa_upper = strtoupper($row['empresa']);
                if (isset($valores_medios[$empresa_upper])) {
                    $valor_medio = $valores_medios[$empresa_upper];
                    $ultima_fatura = floatval($row['ultima_fatura']);

                    if ($ultima_fatura > $valor_medio) {
                        $faturas_ultrapassaram[] = [
                            'empresa' => $row['empresa'],
                            'contrato' => $row['contrato'],
                            'valor_medio' => $valor_medio,
                            'ultima_fatura' => $ultima_fatura,
                            'diferenca' => $ultima_fatura - $valor_medio,
                            'percentual' => (($ultima_fatura - $valor_medio) / $valor_medio) * 100
                        ];
                    }
                }

                // Preparar dados para gráficos
                $dados_mensais = [
                    'Jan' => $row['jan_25'] ? floatval($row['jan_25']) : 0,
                    'Fev' => $row['fev_25'] ? floatval($row['fev_25']) : 0,
                    'Mar' => $row['mar_25'] ? floatval($row['mar_25']) : 0,
                    'Abr' => $row['abr_25'] ? floatval($row['abr_25']) : 0,
                    'Mai' => $row['mai_25'] ? floatval($row['mai_25']) : 0,
                    'Jun' => $row['jun_25'] ? floatval($row['jun_25']) : 0,
                    'Jul' => $row['jul_25'] ? floatval($row['jul_25']) : 0,
                    'Ago' => $row['ago_25'] ? floatval($row['ago_25']) : 0,
                    'Set' => $row['set_25'] ? floatval($row['set_25']) : 0,
                    'Out' => $row['out_25'] ? floatval($row['out_25']) : 0,
                    'Nov' => $row['nov_25'] ? floatval($row['nov_25']) : 0,
                    'Dez' => $row['dez_25'] ? floatval($row['dez_25']) : 0
                ];

                $dados_grafico[$row['empresa']] = $dados_mensais;
            }
        }

        // Calcular média
        $media_valor_anual = $total_contratos > 0 ? $total_valor_anual / $total_contratos : 0;
    }

    // Fechar conexão
    if ($connection_status == "connected") {
        $conn->close();
    }

    // Mapeamento de meses para colunas do banco
    $meses_colunas = [
        'jan' => 'jan_25',
        'fev' => 'fev_25',
        'mar' => 'mar_25',
        'abr' => 'abr_25',
        'mai' => 'mai_25',
        'jun' => 'jun_25',
        'jul' => 'jul_25',
        'ago' => 'ago_25',
        'set' => 'set_25',
        'out' => 'out_25',
        'nov' => 'nov_25',
        'dez' => 'dez_25'
    ];
    ?>

    <!-- Timer de atualização -->
    <div class="refresh-timer">
        <i class="fas fa-sync-alt"></i>
        <span id="refreshCountdown">Atualizando em: 10:00</span>
    </div>

    <!-- Indicador de scroll automático -->
    <div class="auto-scroll-indicator">
        <i class="fas fa-play" id="scrollToggleIcon"></i>
        <span id="scrollStatus">Scroll Automático: ON</span>
    </div>

    <div class="container fade-in">
        <!-- Connection Status -->
        <?php if ($connection_status == "connected"): ?>
            <!-- <div class="connection-status status-connected"> -->
            <!-- <i class="fas fa-check-circle"></i> Conectado ao Banco de Dados -->
    </div>
<?php elseif ($connection_status == "error"): ?>
    <div class="connection-status status-error">
        <i class="fas fa-exclamation-circle"></i> Erro: <?php echo $connection_error; ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="header" id="section-header">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 30px;">
        <!-- Logo e instituição -->
        <div class="logo-container">
            <div style="flex-shrink: 0;">
                <img src="img/babv_logo.png" alt="Logo BABV" class="logo-img">
            </div>
            <div class="institution-info">
                <div>Força Aérea Brasileira</div>
                <div>Base Aérea de Boa Vista</div>
                <div>SÉTIMO COMANDO AÉREO REGIONAL</div>
            </div>
        </div>
        <!-- Header -->
        <header>
            <div class="header-right">
                <div class="logo">
                    <i class="fas"></i>
                    <h1>Contratos <span>ETIC</span></h1>
                </div>
            </div>
        </header>
    </div>
</div>
</div>

<!-- Filters Section -->
<div class="filters-section" id="section-filters">
    <!-- <div class="filter-title">
        <i class="fas fa-filter"></i> Filtros
    </div> -->
    <form method="GET" action="" class="filter-grid">
        <div class="filter-group">
            <label class="filter-label">Filtrar por Mês</label>
            <select name="mes" class="filter-select" onchange="this.form.submit()">
                <option value="todos" <?php echo $mes_filtro == 'todos' ? 'selected' : ''; ?>>Todos os Meses</option>
                <option value="jan" <?php echo $mes_filtro == 'jan' ? 'selected' : ''; ?>>Janeiro 2025</option>
                <option value="fev" <?php echo $mes_filtro == 'fev' ? 'selected' : ''; ?>>Fevereiro 2025</option>
                <option value="mar" <?php echo $mes_filtro == 'mar' ? 'selected' : ''; ?>>Março 2025</option>
                <option value="abr" <?php echo $mes_filtro == 'abr' ? 'selected' : ''; ?>>Abril 2025</option>
                <option value="mai" <?php echo $mes_filtro == 'mai' ? 'selected' : ''; ?>>Maio 2025</option>
                <option value="jun" <?php echo $mes_filtro == 'jun' ? 'selected' : ''; ?>>Junho 2025</option>
                <option value="jul" <?php echo $mes_filtro == 'jul' ? 'selected' : ''; ?>>Julho 2025</option>
                <option value="ago" <?php echo $mes_filtro == 'ago' ? 'selected' : ''; ?>>Agosto 2025</option>
                <option value="set" <?php echo $mes_filtro == 'set' ? 'selected' : ''; ?>>Setembro 2025</option>
                <option value="out" <?php echo $mes_filtro == 'out' ? 'selected' : ''; ?>>Outubro 2025</option>
                <option value="nov" <?php echo $mes_filtro == 'nov' ? 'selected' : ''; ?>>Novembro 2025</option>
                <option value="dez" <?php echo $mes_filtro == 'dez' ? 'selected' : ''; ?>>Dezembro 2025</option>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Filtrar por Empresa</label>
            <select name="empresa" class="filter-select" onchange="this.form.submit()">
                <option value="todos" <?php echo $empresa_filtro == 'todos' ? 'selected' : ''; ?>>Todas as Empresas</option>
                <?php foreach ($empresas as $empresa): ?>
                    <option value="<?php echo htmlspecialchars($empresa); ?>"
                        <?php echo $empresa_filtro == $empresa ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($empresa); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Ações</label>
            <div style="display: flex; gap: 15px;">
                <button type="submit" class="filter-select" style="background: var(--primary-color); color: white; border: none;">
                    <i class="fas fa-search"></i> Aplicar Filtros
                </button>
                <a href="?" class="filter-select" style="text-decoration: none; text-align: center;">
                    <i class="fas fa-times"></i> Limpar Filtros
                </a>
            </div>
        </div>
    </form>
</div>

<?php if ($connection_status == "connected"): ?>

    <!-- KPI Section -->
    <div class="kpi-section" id="section-kpi">
        <div class="kpi-card">
            <div class="kpi-title">Contratos Ativos</div>
            <div class="kpi-value"><?php echo $total_contratos; ?></div>
            <div class="kpi-subtext">Total de contratos vigentes</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Valor Anual Total</div>
            <div class="kpi-value">
                <!-- <i class="fas fa-dollar-sign kpi-icon-bg"></i> -->
                R$ <?php echo number_format($total_valor_anual, 2, ',', '.'); ?>
            </div>
            <div class="kpi-subtext">Soma dos valores anuais</div>
        </div>

        <div class="kpi-card warning">
            <div class="kpi-title">Valor Empenhado Total</div>
            <div class="kpi-value">
                <!-- <i class="fas fa-hand-holding-usd kpi-icon-bg"></i> -->
                R$ <?php echo number_format($total_valor_empenhado, 2, ',', '.'); ?>
            </div>
            <div class="kpi-subtext">Total empenhado nos contratos</div>
        </div>

        <div class="kpi-card danger">
            <div class="kpi-title">Alertas Valor Médio</div>
            <div class="kpi-value"><?php echo count($faturas_ultrapassaram); ?></div>
            <div class="kpi-subtext">Faturas acima do valor médio</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section" id="section-charts">
        <div class="chart-container">
            <div class="section-title">
                <i class="fas fa-chart-bar"></i> Faturas por Empresa
            </div>
            <div class="chart-wrapper" id="section-charts-bar">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <div class="section-title">
                <i class="fas fa-chart-line"></i> Evolução Mensal dos Gastos
            </div>
            <div class="chart-wrapper" id="section-charts-line">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="table-section">
        <div class="section-title" id="section-table">
            <i class="fas fa-table"></i> Detalhamento dos Contratos
        </div>
        <div class="card">
            <table class="data-table table table-striped" style="width:100%;">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Objeto</th>
                        <th>Empresa</th>
                        <th>Vencimento</th>
                        <th>Valor Médio</th>
                        <th>Última Fatura</th>
                        <th>Valor Anual</th>
                        <th>Valor Empenhado</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($contratos)): ?>
                        <?php foreach ($contratos as $contrato): ?>
                            <?php
                            // Determinar status baseado no vencimento
                            $status_class = "badge-active";
                            $status_text = "VIGENTE";

                            if (!empty($contrato['vencimento_original'])) {
                                $vencimento = new DateTime($contrato['vencimento_original']);
                                $hoje = new DateTime();
                                $diferenca = $hoje->diff($vencimento);
                                $dias_para_vencer = $diferenca->days;

                                if ($diferenca->invert == 1) {
                                    $status_class = "badge-danger";
                                    $status_text = "VENCIDO";
                                } elseif ($dias_para_vencer < 30) {
                                    $status_class = "badge-danger";
                                    $status_text = "CRÍTICO";
                                } elseif ($dias_para_vencer < 90) {
                                    $status_class = "badge-warning";
                                    $status_text = "ALERTA";
                                }
                            } elseif (strpos($contrato['vencimento_formatado'], '**') !== false) {
                                $status_class = "badge-warning";
                                $status_text = "DATA INC.";
                            }

                            // Verificar se última fatura ultrapassou valor médio
                            $empresa_upper = strtoupper($contrato['empresa']);
                            $ultrapassou_valor_medio = false;

                            if (isset($valores_medios[$empresa_upper])) {
                                $valor_medio = $valores_medios[$empresa_upper];
                                $ultima_fatura = floatval($contrato['ultima_fatura']);
                                $ultrapassou_valor_medio = $ultima_fatura > $valor_medio;
                            }
                            ?>

                            <tr style="<?php echo $ultrapassou_valor_medio ? 'background-color: #fef2f2;' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($contrato['contrato']); ?></strong></td>
                                <td><?php echo htmlspecialchars($contrato['objeto']); ?></td>
                                <td><?php echo htmlspecialchars($contrato['empresa']); ?></td>
                                <td><?php echo $contrato['vencimento_formatado']; ?></td>
                                <td style="color: #ef4444; font-weight: 600;">
                                    <?php
                                    $empresa_key = strtoupper($contrato['empresa']);
                                    if (isset($valores_medios[$empresa_key])) {
                                        echo 'R$ ' . number_format($valores_medios[$empresa_key], 2, ',', '.');
                                    } else {
                                        echo htmlspecialchars($contrato['valor_medio_mensal']);
                                    }
                                    ?>
                                </td>
                                <td style="color: #ef4444; font-weight: 600;">
                                    <span style="display: flex; align-items: center; gap: 10px;">
                                        R$ <?php echo number_format($contrato['ultima_fatura'], 2, ',', '.'); ?>
                                        <?php if ($ultrapassou_valor_medio): ?>
                                            <i class="fas fa-exclamation-triangle" style="color: #ef4444;" title="Ultrapassou o valor médio"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td style="color: #ef4444; font-weight: 600;">
                                    <?php if (!empty($contrato['valor_anual'])): ?>
                                        R$ <?php echo number_format($contrato['valor_anual'], 2, ',', '.'); ?>
                                    <?php else: ?>
                                        <span style="color: #999; font-weight: normal;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="color: #1e40af;">R$ <?php echo number_format($contrato['valor_empenhado'], 2, ',', '.'); ?></strong></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 60px;">
                                <i class="fas fa-database" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                                <p style="font-size: 28px;">Nenhum contrato encontrado com os filtros aplicados.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Monthly Values Table (Filtrada por Mês) -->
    <?php if ($mes_filtro != 'todos' && isset($meses_colunas[$mes_filtro])): ?>
        <?php
        $mes_nome = [
            'jan' => 'Janeiro',
            'fev' => 'Fevereiro',
            'mar' => 'Março',
            'abr' => 'Abril',
            'mai' => 'Maio',
            'jun' => 'Junho',
            'jul' => 'Julho',
            'ago' => 'Agosto',
            'set' => 'Setembro',
            'out' => 'Outubro',
            'nov' => 'Novembro',
            'dez' => 'Dezembro'
        ];
        $coluna_mes = $meses_colunas[$mes_filtro];
        ?>

        <div class="table-section" id="section-monthly">
            <div class="section-title">
                <i class="fas fa-calendar-alt"></i> Valores de <?php echo $mes_nome[$mes_filtro]; ?> 2025
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Empresa</th>
                        <th>Valor <?php echo $mes_nome[$mes_filtro]; ?></th>
                        <th>Valor Médio</th>
                        <th>Diferença</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($contratos)): ?>
                        <?php foreach ($contratos as $contrato): ?>
                            <?php
                            $valor_mes = !empty($contrato[$coluna_mes]) ? floatval($contrato[$coluna_mes]) : 0;
                            $empresa_key = strtoupper($contrato['empresa']);
                            $valor_medio = isset($valores_medios[$empresa_key]) ? $valores_medios[$empresa_key] : 0;
                            $diferenca = $valor_mes - $valor_medio;
                            $percentual = $valor_medio > 0 ? ($diferenca / $valor_medio) * 100 : 0;

                            // Determinar status
                            if ($valor_mes == 0) {
                                $status_class = '';
                                $status_text = 'SEM DADO';
                                $status_color = '#999';
                            } elseif ($diferenca > 0) {
                                $status_class = 'badge-danger';
                                $status_text = 'ACIMA';
                                $status_color = '#ef4444';
                            } elseif ($diferenca < 0) {
                                $status_class = 'badge-success';
                                $status_text = 'ABAIXO';
                                $status_color = '#10b981';
                            } else {
                                $status_class = 'badge-active';
                                $status_text = 'NO PADRÃO';
                                $status_color = '#3b82f6';
                            }
                            ?>

                            <tr>
                                <td><strong><?php echo htmlspecialchars($contrato['contrato']); ?></strong></td>
                                <td><?php echo htmlspecialchars($contrato['empresa']); ?></td>
                                <td>
                                    <?php if ($valor_mes > 0): ?>
                                        <strong style="color: #1e40af;">R$ <?php echo number_format($valor_mes, 2, ',', '.'); ?></strong>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #ef4444; font-weight: 600;">R$ <?php echo number_format($valor_medio, 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($valor_mes > 0): ?>
                                        <span style="color: <?php echo $diferenca > 0 ? '#ef4444' : ($diferenca < 0 ? '#10b981' : '#64748b'); ?>; font-weight: 600;">
                                            <?php echo $diferenca > 0 ? '+' : ''; ?>
                                            R$ <?php echo number_format($diferenca, 2, ',', '.'); ?>
                                            (<?php echo number_format($percentual, 1, ',', '.'); ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo $status_class; ?>" style="<?php echo $valor_mes == 0 ? 'background-color: #f1f5f9; color: #64748b;' : ''; ?>">
                                        <?php echo $status_text; ?>
                                    </span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Alert Section -->
    <div class="alert-section" id="section-alerts">
        <div class="alert-title">
            <i class="fas fa-exclamation-triangle"></i> Alertas e Observações
        </div>
        <ul class="alert-list">
            <?php if (count($faturas_ultrapassaram) > 0): ?>
                <?php foreach ($faturas_ultrapassaram as $alerta): ?>
                    <li>
                        <div class="alert-icon high">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <div class="alert-content">
                            <h4>Fatura ultrapassou valor médio</h4>
                            <p>
                                <strong><?php echo htmlspecialchars($alerta['empresa']); ?></strong> (<?php echo htmlspecialchars($alerta['contrato']); ?>):
                                Última fatura de R$ <?php echo number_format($alerta['ultima_fatura'], 2, ',', '.'); ?>
                                ultrapassou o valor médio de R$ <?php echo number_format($alerta['valor_medio'], 2, ',', '.'); ?>
                            </p>
                        </div>
                        <div class="alert-value" style="color: #ef4444; font-weight: 700;">
                            +R$ <?php echo number_format($alerta['diferenca'], 2, ',', '.'); ?>
                            (<?php echo number_format($alerta['percentual'], 1, ',', '.'); ?>%)
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($proximos_vencimentos > 0): ?>
                <li>
                    <div class="alert-icon medium">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="alert-content">
                        <h4>Vencimentos próximos</h4>
                        <p><?php echo $proximos_vencimentos; ?> contrato(s) com vencimento em menos de 90 dias</p>
                    </div>
                </li>
            <?php endif; ?>

            <?php
            // Verificar contratos com data incompleta
            $datas_incompletas = 0;
            foreach ($contratos as $c) {
                if (strpos($c['vencimento_formatado'], '**') !== false) {
                    $datas_incompletas++;
                }
            }

            if ($datas_incompletas > 0): ?>
                <li>
                    <div class="alert-icon medium">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="alert-content">
                        <h4>Datas incompletas</h4>
                        <p><?php echo $datas_incompletas; ?> contrato(s) com data de vencimento incompleta</p>
                    </div>
                </li>
            <?php endif; ?>

            <?php
            // Verificar contratos sem valor anual
            $sem_valor_anual = 0;
            foreach ($contratos as $c) {
                if (empty($c['valor_anual']) || $c['valor_anual'] == 0) {
                    $sem_valor_anual++;
                }
            }

            if ($sem_valor_anual > 0): ?>
                <li>
                    <div class="alert-icon low">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="alert-content">
                        <h4>Valores anuais não informados</h4>
                        <p><?php echo $sem_valor_anual; ?> contrato(s) sem valor anual definido</p>
                    </div>
                </li>
            <?php endif; ?>

            <li id="section-alerts-resumo">
                <div class="alert-icon" style="background-color: #3b82f6;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-content">
                    <h4>Resumo do relatório</h4>
                    <p>
                        Total: <?php echo $total_contratos; ?> contrato(s) |
                        Filtro: <?php echo $empresa_filtro == 'todos' ? 'Todas empresas' : $empresa_filtro; ?> |
                        Mês: <?php echo $mes_filtro == 'todos' ? 'Todos meses' : $mes_nome[$mes_filtro]; ?>
                    </p>
                </div>
            </li>
        </ul>
    </div>

<?php else: ?>
    <!-- Error State -->
    <div class="loading">
        <i class="fas fa-exclamation-triangle fa-4x"></i>
        <h2 style="font-size: 36px; margin: 20px 0;">Erro de Conexão com o Banco de Dados</h2>
        <p style="font-size: 24px; margin-bottom: 20px;">Não foi possível conectar ao banco de dados. Verifique:</p>
        <ul style="text-align: left; max-width: 800px; margin: 30px auto; background: #fee; padding: 30px; border-radius: 12px; font-size: 22px;">
            <li>Servidor MySQL está em execução</li>
            <li>Credenciais de acesso corretas</li>
            <li>Banco de dados "<?php echo $dbname; ?>" existe</li>
            <li>Usuário "admin" tem permissões de acesso</li>
        </ul>
        <p style="font-size: 24px; margin-top: 20px;"><strong>Detalhes do erro:</strong> <?php echo $connection_error; ?></p>
    </div>
<?php endif; ?>

<!-- Footer -->
<div class="footer" id="section-footer">
    <p>ETIC - Esquadrão de Tecnologia da Informação e Comunicações</p>
    <p>© 2025 Todos os direitos reservados.</p>
    <p id="currentTime">Atualizado: <?php echo date('d/m/Y H:i:s'); ?></p>
</div>
<p style="font-size: 12px; text-align: center; margin-right: 20px; margin-top: 10px; color: var(--gray-color);">
    Desenvolvedor 3S TIN Jônatas - Versão inicial: 1.1
</p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurações do scroll automático
        let autoScrollEnabled = true;
        let currentSectionIndex = 0;
        let scrollInterval;
        const sections = [
            'section-header',
            'section-kpi',
            'section-charts-bar',
            'section-charts-line',
            'section-table',
            'section-monthly',
            'section-alerts',
            'section-alerts-resumo',
            'section-footer'
        ].filter(id => document.getElementById(id)); // Filtra apenas seções que existem

        // Configurações do timer de atualização
        let refreshTimer = 10 * 60; // 10 minutos em segundos
        let refreshInterval;

        // Atualizar hora automaticamente
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('pt-BR');
            const dateString = now.toLocaleDateString('pt-BR');

            const timeElements = document.querySelectorAll('#currentTime');
            if (timeElements.length > 0) {
                timeElements[0].textContent = 'Atualizado: ' + dateString + ' ' + timeString;
            }
        }

        // Atualizar contador de refresh
        function updateRefreshTimer() {
            const minutes = Math.floor(refreshTimer / 60);
            const seconds = refreshTimer % 60;
            document.getElementById('refreshCountdown').textContent =
                `Atualizando em: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            if (refreshTimer <= 0) {
                location.reload();
            } else {
                refreshTimer--;
            }
        }

        // Focar em uma seção específica
        function focusSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                // Remover foco anterior
                sections.forEach(id => {
                    const elem = document.getElementById(id);
                    if (elem) {
                        elem.classList.remove('section-focus');
                    }
                });

                // Adicionar foco na seção atual
                section.classList.add('section-focus');

                // Scroll suave para a seção
                section.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }

        // Avançar para próxima seção
        function nextSection() {
            if (!autoScrollEnabled) return;

            currentSectionIndex = (currentSectionIndex + 1) % sections.length;
            focusSection(sections[currentSectionIndex]);
        }

        // Iniciar scroll automático
        function startAutoScroll() {
            if (scrollInterval) clearInterval(scrollInterval);

            scrollInterval = setInterval(() => {
                nextSection();
            }, 10000); // 10 segundos por seção

            document.getElementById('scrollStatus').textContent = 'Scroll Automático: ON';
            document.getElementById('scrollToggleIcon').className = 'fas fa-play';
        }

        // Parar scroll automático
        function stopAutoScroll() {
            if (scrollInterval) {
                clearInterval(scrollInterval);
                scrollInterval = null;
            }
            document.getElementById('scrollStatus').textContent = 'Scroll Automático: OFF';
            document.getElementById('scrollToggleIcon').className = 'fas fa-pause';
        }

        // Toggle scroll automático
        document.querySelector('.auto-scroll-indicator').addEventListener('click', function() {
            autoScrollEnabled = !autoScrollEnabled;
            if (autoScrollEnabled) {
                startAutoScroll();
            } else {
                stopAutoScroll();
            }
        });

        // Pausar scroll ao interagir com a página
        document.addEventListener('click', function() {
            if (autoScrollEnabled) {
                stopAutoScroll();
                // Reiniciar após 30 segundos de inatividade
                setTimeout(() => {
                    if (autoScrollEnabled) {
                        startAutoScroll();
                    }
                }, 30000);
            }
        });

        document.addEventListener('wheel', function() {
            if (autoScrollEnabled) {
                stopAutoScroll();
                setTimeout(() => {
                    if (autoScrollEnabled) {
                        startAutoScroll();
                    }
                }, 30000);
            }
        });

        // Inicializar
        updateTime();
        setInterval(updateTime, 60000); // Atualizar hora a cada minuto

        refreshInterval = setInterval(updateRefreshTimer, 1000); // Atualizar timer a cada segundo

        startAutoScroll(); // Iniciar scroll automático

        // Efeito de hover nas linhas da tabela
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f0f9ff';
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'all 0.2s ease';
            });

            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = 'scale(1)';
            });
        });

        // Gráficos
        <?php if ($connection_status == "connected" && !empty($dados_grafico)): ?>

            // Preparar dados para gráfico de barras
            const empresas = <?php echo json_encode(array_keys($dados_grafico)); ?>;
            const totaisPorEmpresa = empresas.map(empresa => {
                const dados = <?php echo json_encode($dados_grafico); ?>[empresa];
                return Object.values(dados).reduce((a, b) => a + b, 0);
            });

            // Gráfico de Barras - Distribuição por Empresa
            const barCtx = document.getElementById('barChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: empresas,
                    datasets: [{
                        label: 'Total Faturado (R$)',
                        data: totaisPorEmpresa,
                        backgroundColor: [
                            'rgba(37, 99, 235, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)'
                        ],
                        borderColor: [
                            'rgba(37, 99, 235, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(139, 92, 246, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.raw.toLocaleString('pt-BR', {
                                        minimumFractionDigits: 2
                                    });
                                }
                            },
                            titleFont: {
                                size: 20
                            },
                            bodyFont: {
                                size: 18
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                },
                                font: {
                                    size: 18
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 18
                                }
                            }
                        }
                    }
                }
            });

            // Gráfico de Linhas - Evolução Mensal
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            const datasets = [];

            const cores = [
                'rgb(37, 99, 235)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
                'rgb(139, 92, 246)'
            ];

            let corIndex = 0;
            for (const empresa in <?php echo json_encode($dados_grafico); ?>) {
                const dados = <?php echo json_encode($dados_grafico); ?>[empresa];
                const valores = meses.map(mes => dados[mes] || 0);

                // Só mostrar empresas com dados
                if (valores.some(v => v > 0)) {
                    datasets.push({
                        label: empresa,
                        data: valores,
                        borderColor: cores[corIndex % cores.length],
                        backgroundColor: cores[corIndex % cores.length] + '20',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: false,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    });
                    corIndex++;
                }
            }

            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': R$ ' +
                                        context.raw.toLocaleString('pt-BR', {
                                            minimumFractionDigits: 2
                                        });
                                }
                            },
                            titleFont: {
                                size: 20
                            },
                            bodyFont: {
                                size: 18
                            }
                        },
                        legend: {
                            labels: {
                                font: {
                                    size: 18
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                },
                                font: {
                                    size: 18
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 18
                                }
                            }
                        }
                    }
                }
            });

        <?php endif; ?>

        // Redimensionar gráficos quando a janela for redimensionada
        window.addEventListener('resize', function() {
            if (window.barChart) window.barChart.resize();
            if (window.lineChart) window.lineChart.resize();
        });

        // Atualizar automaticamente a página a cada 10 minutos
        setTimeout(function() {
            location.reload();
        }, 10 * 60 * 1000); // 10 minutos
    });
</script>
<script src="js/charts.js"></script>
</body>

</html>