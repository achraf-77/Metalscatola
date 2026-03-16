<?php
require __DIR__ . "/cnx.php";
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Importer un fichier Excel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Import Page Specific Styles ── */
        .import-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 40px;
            margin-top: 24px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            transition: box-shadow .3s var(--ease);
        }
        .import-card:hover { box-shadow: var(--shadow-md); }

        .import-card h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            padding-bottom: 0;
            border-bottom: none;
            justify-content: center;
        }

        .import-subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: .9rem;
            margin-bottom: 28px;
        }

        /* Drop zone */
        .drop-zone {
            border: 2.5px dashed var(--gray-300);
            border-radius: var(--radius-lg);
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all .3s var(--ease);
            background: var(--gray-50);
            position: relative;
        }
        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: var(--brand-red-1);
            background: rgba(224, 49, 49, .04);
            transform: scale(1.01);
        }
        .drop-zone.dragover {
            box-shadow: 0 0 0 4px rgba(224, 49, 49, .12);
        }

        .drop-zone-icon {
            font-size: 3rem;
            margin-bottom: 12px;
            display: block;
            opacity: .6;
        }

        .drop-zone-text {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .drop-zone-text span {
            color: var(--brand-red-1);
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .drop-zone-hint {
            font-size: .8rem;
            color: var(--text-muted);
            margin-top: 8px;
        }

        .file-input {
            display: none;
        }

        /* Selected file info */
        .file-info {
            display: none;
            align-items: center;
            gap: 14px;
            margin-top: 18px;
            padding: 14px 18px;
            background: linear-gradient(135deg, rgba(224, 49, 49, .06), rgba(224, 49, 49, .02));
            border: 1px solid rgba(224, 49, 49, .15);
            border-radius: var(--radius-md);
        }
        .file-info.show { display: flex; }

        .file-info-icon {
            font-size: 1.8rem;
            flex-shrink: 0;
        }
        .file-info-details {
            flex: 1;
        }
        .file-info-name {
            font-weight: 700;
            color: var(--text-primary);
            font-size: .95rem;
        }
        .file-info-size {
            font-size: .8rem;
            color: var(--text-muted);
        }
        .file-info-remove {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.3rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all .2s;
        }
        .file-info-remove:hover {
            background: rgba(224, 49, 49, .1);
            color: var(--brand-red-1);
        }

        /* Column mapping section */
        .mapping-section {
            display: none;
            margin-top: 28px;
        }
        .mapping-section.show { display: block; }

        .mapping-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mapping-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .mapping-item {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 12px;
            transition: all .2s var(--ease);
        }
        .mapping-item:hover {
            border-color: var(--gray-400);
            background: #fff;
        }

        .mapping-item label {
            font-size: .72rem;
            margin-bottom: 6px;
        }

        .mapping-item select {
            font-size: .85rem;
            padding: 8px 10px;
        }

        /* Preview table */
        .preview-section {
            display: none;
            margin-top: 24px;
        }
        .preview-section.show { display: block; }

        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .preview-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-count {
            font-size: .85rem;
            color: var(--text-muted);
            background: var(--gray-100);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .preview-table-wrap {
            max-height: 350px;
            overflow: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }

        .preview-table-wrap table {
            font-size: .82rem;
            min-width: 800px;
        }

        /* Import actions */
        .import-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-top: 28px;
        }

        /* Progress */
        .import-progress {
            display: none;
            margin-top: 24px;
        }
        .import-progress.show { display: block; }

        .progress-bar-wrap {
            height: 8px;
            background: var(--gray-200);
            border-radius: 100px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--brand-red-1), var(--brand-red-2));
            border-radius: 100px;
            transition: width .3s var(--ease);
            width: 0%;
        }

        .progress-text {
            text-align: center;
            font-size: .9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Result */
        .import-result {
            display: none;
            margin-top: 20px;
            padding: 18px 22px;
            border-radius: var(--radius-md);
            font-size: .92rem;
            font-weight: 500;
        }
        .import-result.show { display: block; }

        .import-result.success {
            background: linear-gradient(135deg, rgba(34, 139, 34, .08), rgba(34, 139, 34, .03));
            border: 1px solid rgba(34, 139, 34, .2);
            color: #1a7a1a;
        }
        .import-result.error {
            background: linear-gradient(135deg, rgba(224, 49, 49, .08), rgba(224, 49, 49, .03));
            border: 1px solid rgba(224, 49, 49, .2);
            color: var(--brand-red-2);
        }

        .result-icon { font-size: 1.2rem; margin-right: 8px; }

        .result-details {
            margin-top: 10px;
            font-size: .85rem;
            color: var(--text-secondary);
        }

        /* Expected format info */
        .format-info {
            margin-top: 28px;
            padding: 18px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
        }
        .format-info-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .format-cols {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .format-col-tag {
            background: linear-gradient(135deg, var(--brand-gray-1), var(--brand-gray-2));
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .import-card { padding: 24px 16px; }
            .drop-zone { padding: 30px 16px; }
            .mapping-grid { grid-template-columns: 1fr; }
            .import-actions { flex-direction: column; }
        }
    </style>
</head>

<body>
    <div class="container wide">

        <nav>
            <a href="form.php">+ Ajouter</a>
            <a href="table_d109.php">D109</a>
            <a href="table_d180.php">D180</a>
            <a href="table_d305.php">D305</a>
            <a href="table_autres.php">Autres</a>
            <a href="import_excel.php" class="active">📥 Importer Excel</a>
        </nav>

        <div class="import-card">
            <h2>📥 Importer un fichier Excel</h2>
            <p class="import-subtitle">Chargez un fichier Excel (.xlsx, .xls, .csv) pour importer les données dans la base</p>

            <!-- Drop Zone -->
            <div class="drop-zone" id="dropZone">
                <span class="drop-zone-icon">📄</span>
                <p class="drop-zone-text">Glissez votre fichier ici ou <span>cliquez pour parcourir</span></p>
                <p class="drop-zone-hint">Formats acceptés : .xlsx, .xls, .csv — Taille max : 10 Mo</p>
                <input type="file" class="file-input" id="fileInput" accept=".xlsx,.xls,.csv">
            </div>

            <!-- File Info -->
            <div class="file-info" id="fileInfo">
                <span class="file-info-icon">📊</span>
                <div class="file-info-details">
                    <div class="file-info-name" id="fileName"></div>
                    <div class="file-info-size" id="fileSize"></div>
                </div>
                <button class="file-info-remove" id="fileRemove" title="Supprimer">✕</button>
            </div>

            <!-- Column Mapping -->
            <div class="mapping-section" id="mappingSection">
                <div class="mapping-title">🔗 Mapping des colonnes</div>
                <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:16px;">
                    Associez chaque colonne de la base de données à une colonne de votre fichier Excel.
                </p>
                <div class="mapping-grid" id="mappingGrid">
                    <!-- Will be populated dynamically -->
                </div>
            </div>

            <!-- Preview -->
            <div class="preview-section" id="previewSection">
                <div class="preview-header">
                    <div class="preview-title">👁️ Aperçu des données</div>
                    <div class="preview-count" id="previewCount">0 lignes</div>
                </div>
                <div class="preview-table-wrap">
                    <table id="previewTable">
                        <thead id="previewHead"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Import Actions -->
            <div class="import-actions" id="importActions" style="display:none;">
                <button class="btn primary" id="btnCancel" onclick="resetAll()">Annuler</button>
                <button class="btn green" id="btnImport" onclick="startImport()">📥 Importer dans la base</button>
            </div>

            <!-- Progress -->
            <div class="import-progress" id="importProgress">
                <div class="progress-bar-wrap">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="progress-text" id="progressText">Importation en cours...</div>
            </div>

            <!-- Result -->
            <div class="import-result" id="importResult"></div>

            <!-- Expected Format -->
            <div class="format-info">
                <div class="format-info-title">ℹ️ Colonnes attendues dans la base de données</div>
                <div class="format-cols">
                    <span class="format-col-tag">REF (clé) *</span>
                    <span class="format-col-tag">DESCRIPTION</span>
                    <span class="format-col-tag">FORMAT (obligatoire) *</span>
                    <span class="format-col-tag">CLIENT</span>
                    <span class="format-col-tag">STOCK PF</span>
                    <span class="format-col-tag">STOCK FB</span>
                    <span class="format-col-tag">ARRIVAGE</span>
                    <span class="format-col-tag">CDE ITALIE</span>
                    <span class="format-col-tag" style="background:linear-gradient(135deg,#2d6a4f,#40916c)">STOCK (calculé)</span>
                    <span class="format-col-tag" style="background:linear-gradient(135deg,#2d6a4f,#40916c)">COUVERTURE (calculé)</span>
                </div>
                <p style="font-size:.78rem;color:var(--text-muted);margin-top:10px;">
                    * = obligatoire &nbsp;|&nbsp; <strong>FORMAT</strong> détermine le tableau de destination (D109, D180, D305, Autres) &nbsp;|&nbsp; <strong>STOCK</strong> = Stock PF + Stock FB &nbsp;|&nbsp; <strong>COUVERTURE</strong> = Stock + Arrivage + Cde Italie
                </p>
            </div>
        </div>

    </div>

    <!-- SheetJS library for reading Excel files -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

    <script>
        // ── State ──
        let parsedData = [];      // Array of row objects from Excel
        let excelHeaders = [];    // Column headers from Excel
        const dbColumns = [
            { key: 'ref',         label: 'REF (Référence)',  required: true },
            { key: 'description', label: 'DESCRIPTION',      required: false },
            { key: 'format',      label: 'FORMAT',           required: true },
            { key: 'client',      label: 'CLIENT',           required: false },
            { key: 'stock_pf',    label: 'STOCK PF',         required: false },
            { key: 'stock_fb',    label: 'STOCK FB',         required: false },
            { key: 'arrivage',    label: 'ARRIVAGE',         required: false },
            { key: 'cde_italie',  label: 'CDE ITALIE',      required: false }
        ];

        // ── DOM Elements ──
        const dropZone    = document.getElementById('dropZone');
        const fileInput   = document.getElementById('fileInput');
        const fileInfo    = document.getElementById('fileInfo');
        const fileName    = document.getElementById('fileName');
        const fileSize    = document.getElementById('fileSize');
        const fileRemove  = document.getElementById('fileRemove');
        const mappingSection = document.getElementById('mappingSection');
        const mappingGrid = document.getElementById('mappingGrid');
        const previewSection = document.getElementById('previewSection');
        const previewCount = document.getElementById('previewCount');
        const previewHead = document.getElementById('previewHead');
        const previewBody = document.getElementById('previewBody');
        const importActions = document.getElementById('importActions');
        const importProgress = document.getElementById('importProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const importResult = document.getElementById('importResult');

        // ── Drop Zone Events ──
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) handleFile(files[0]);
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) handleFile(fileInput.files[0]);
        });

        fileRemove.addEventListener('click', resetAll);

        // ── Handle File ──
        function handleFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!['xlsx', 'xls', 'csv'].includes(ext)) {
                alert('Format non supporté ! Utilisez .xlsx, .xls ou .csv');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert('Le fichier est trop volumineux (max 10 Mo).');
                return;
            }

            // Show file info
            fileName.textContent = file.name;
            fileSize.textContent = formatBytes(file.size);
            fileInfo.classList.add('show');
            dropZone.style.display = 'none';

            // Read file
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });

                    if (!jsonData.length) {
                        alert('Le fichier est vide ou ne contient pas de données valides.');
                        resetAll();
                        return;
                    }

                    excelHeaders = Object.keys(jsonData[0]);
                    parsedData = jsonData;

                    buildMappingUI();
                    updatePreview();
                    importActions.style.display = 'flex';
                } catch (err) {
                    alert('Erreur lors de la lecture du fichier : ' + err.message);
                    resetAll();
                }
            };
            reader.readAsArrayBuffer(file);
        }

        // ── Build Mapping UI ──
        function buildMappingUI() {
            mappingGrid.innerHTML = '';

            dbColumns.forEach(col => {
                const item = document.createElement('div');
                item.className = 'mapping-item';

                const lbl = document.createElement('label');
                lbl.textContent = col.label + (col.required ? ' *' : '');
                item.appendChild(lbl);

                const sel = document.createElement('select');
                sel.id = 'map_' + col.key;
                sel.addEventListener('change', updatePreview);

                // Add "ignore" option
                const optIgnore = document.createElement('option');
                optIgnore.value = '';
                optIgnore.textContent = '— Ne pas importer —';
                sel.appendChild(optIgnore);

                // Add Excel column options
                excelHeaders.forEach(h => {
                    const opt = document.createElement('option');
                    opt.value = h;
                    opt.textContent = h;

                    // Auto-match by similarity
                    if (autoMatch(col.key, h)) {
                        opt.selected = true;
                    }
                    sel.appendChild(opt);
                });

                item.appendChild(sel);
                mappingGrid.appendChild(item);
            });

            mappingSection.classList.add('show');
        }

        // ── Auto-match column names ──
        function autoMatch(dbKey, excelHeader) {
            const h = excelHeader.toLowerCase().replace(/[^a-z0-9]/g, '');
            const k = dbKey.toLowerCase().replace(/[^a-z0-9]/g, '');

            // Direct match
            if (h === k) return true;

            // Common mappings
            const mappings = {
                'ref':         ['ref', 'reference', 'référence', 'réf', 'code'],
                'description': ['description', 'desc', 'désignation', 'designation', 'libelle', 'libellé', 'nom'],
                'format':      ['format', 'fmt', 'taille', 'dimension'],
                'client':      ['client', 'clt', 'customer', 'acheteur'],
                'stock_pf':    ['stockpf', 'pf', 'produitfini', 'produitsfinis'],
                'stock_fb':    ['stockfb', 'fb', 'fabrication'],
                'arrivage':    ['arrivage', 'arr', 'reception', 'réception'],
                'cde_italie':  ['cdeitalie', 'cdeit', 'commandeitalie', 'italie', 'cde']
            };

            if (mappings[dbKey]) {
                return mappings[dbKey].includes(h);
            }
            return false;
        }

        // ── Get current mapping ──
        function getMapping() {
            const mapping = {};
            dbColumns.forEach(col => {
                const sel = document.getElementById('map_' + col.key);
                if (sel && sel.value) {
                    mapping[col.key] = sel.value;
                }
            });
            return mapping;
        }

        // ── Update Preview ──
        function updatePreview() {
            const mapping = getMapping();
            const mappedKeys = Object.keys(mapping);

            if (mappedKeys.length === 0) {
                previewSection.classList.remove('show');
                return;
            }

            // Add computed columns to header
            const allHeaderKeys = [...mappedKeys];
            const hasNumericCols = mapping.stock_pf || mapping.stock_fb || mapping.arrivage || mapping.cde_italie;
            if (hasNumericCols) {
                allHeaderKeys.push('_stock', '_couverture');
            }

            // Build header
            previewHead.innerHTML = '<tr>' +
                allHeaderKeys.map(k => {
                    if (k === '_stock') return '<th style="background:linear-gradient(135deg,#2d6a4f,#40916c)">STOCK</th>';
                    if (k === '_couverture') return '<th style="background:linear-gradient(135deg,#2d6a4f,#40916c)">COUVERTURE</th>';
                    const col = dbColumns.find(c => c.key === k);
                    return '<th>' + (col ? col.label : k) + '</th>';
                }).join('') + '</tr>';

            // Build body (first 15 rows)
            const previewRows = parsedData.slice(0, 15);
            previewBody.innerHTML = previewRows.map(row => {
                // Get numeric values for computed columns
                const pf  = parseInt(row[mapping.stock_pf] || 0, 10) || 0;
                const fb  = parseInt(row[mapping.stock_fb] || 0, 10) || 0;
                const arr = parseInt(row[mapping.arrivage] || 0, 10) || 0;
                const cde = parseInt(row[mapping.cde_italie] || 0, 10) || 0;
                const stock = pf + fb;
                const couverture = stock + arr + cde;

                return '<tr>' + allHeaderKeys.map(k => {
                    if (k === '_stock') return '<td style="font-weight:700;color:#2d6a4f">' + stock + '</td>';
                    if (k === '_couverture') return '<td style="font-weight:700;color:#2d6a4f">' + couverture + '</td>';
                    const val = row[mapping[k]] ?? '';
                    return '<td>' + escapeHtml(String(val)) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            previewCount.textContent = parsedData.length + ' ligne' + (parsedData.length > 1 ? 's' : '');
            previewSection.classList.add('show');
        }

        // ── Start Import ──
        async function startImport() {
            const mapping = getMapping();

            // Validate required fields
            if (!mapping.ref) {
                alert('Vous devez mapper la colonne REF (Référence) pour pouvoir importer.');
                return;
            }

            if (!mapping.format) {
                alert('Vous devez mapper la colonne FORMAT pour pouvoir importer.\nLe FORMAT est obligatoire car il détermine le tableau de destination (D109, D180, D305 ou Autres).');
                return;
            }

            // Prepare data
            const rows = parsedData.map(row => {
                const obj = {};
                dbColumns.forEach(col => {
                    if (mapping[col.key]) {
                        let val = row[mapping[col.key]] ?? '';
                        // Convert numeric fields to numbers
                        if (['stock_pf', 'stock_fb', 'arrivage', 'cde_italie'].includes(col.key)) {
                            val = parseInt(val, 10) || 0;
                        } else {
                            val = String(val).trim();
                        }
                        obj[col.key] = val;
                    } else {
                        obj[col.key] = ['stock_pf', 'stock_fb', 'arrivage', 'cde_italie'].includes(col.key) ? 0 : '';
                    }
                });
                return obj;
            });

            // Filter out rows with empty REF
            const validRows = rows.filter(r => String(r.ref).trim() !== '');

            if (!validRows.length) {
                alert('Aucune ligne valide à importer (toutes les REF sont vides).');
                return;
            }

            // Confirm
            if (!confirm('Vous allez importer ' + validRows.length + ' ligne(s) dans la base de données.\nLes doublons (même REF) seront mis à jour.\n\nContinuer ?')) {
                return;
            }

            // Show progress
            importActions.style.display = 'none';
            importProgress.classList.add('show');
            importResult.classList.remove('show');

            // Send in batches of 50
            const batchSize = 50;
            let imported = 0;
            let errors = 0;
            let errorMessages = [];

            for (let i = 0; i < validRows.length; i += batchSize) {
                const batch = validRows.slice(i, i + batchSize);

                try {
                    const response = await fetch('import_process.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ rows: batch })
                    });

                    // Read response as text first to handle PHP errors
                    const responseText = await response.text();
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseErr) {
                        // PHP returned non-JSON (probably an error page)
                        console.error('Server response (non-JSON):', responseText);
                        errors += batch.length;
                        errorMessages.push('Erreur serveur: réponse invalide. Vérifiez la console pour plus de détails.');
                        continue;
                    }

                    if (result.success) {
                        imported += result.imported;
                        if (result.errors) {
                            errors += result.errors;
                            if (result.errorMessages) {
                                errorMessages.push(...result.errorMessages);
                            }
                        }
                    } else {
                        errors += batch.length;
                        errorMessages.push(result.message || 'Erreur serveur');
                    }
                } catch (err) {
                    console.error('Fetch error:', err);
                    errors += batch.length;
                    errorMessages.push('Erreur réseau: ' + err.message);
                }

                // Update progress
                const progress = Math.min(100, Math.round(((i + batch.length) / validRows.length) * 100));
                progressBar.style.width = progress + '%';
                progressText.textContent = 'Importation en cours... ' + (i + batch.length) + ' / ' + validRows.length;
            }

            // Done
            progressBar.style.width = '100%';
            progressText.textContent = 'Terminé !';

            setTimeout(function() {
                importProgress.classList.remove('show');

                if (errors === 0) {
                    importResult.className = 'import-result show success';
                    importResult.innerHTML =
                        '<span class="result-icon">✅</span>' +
                        '<strong>' + imported + ' ligne(s) importée(s) avec succès !</strong>' +
                        '<div class="result-details">' +
                            'Les données ont été ajoutées / mises à jour dans la base de données.' +
                            '<br><br>' +
                            '<a href="table_d109.php" class="btn primary" style="margin-right:8px;">Voir D109</a>' +
                            '<a href="table_d180.php" class="btn primary" style="margin-right:8px;">Voir D180</a>' +
                            '<a href="table_d305.php" class="btn primary" style="margin-right:8px;">Voir D305</a>' +
                            '<a href="table_autres.php" class="btn primary">Voir Autres</a>' +
                        '</div>';
                } else {
                    importResult.className = 'import-result show ' + (imported > 0 ? 'success' : 'error');
                    importResult.innerHTML =
                        '<span class="result-icon">' + (imported > 0 ? '⚠️' : '❌') + '</span>' +
                        '<strong>' + imported + ' importée(s), ' + errors + ' erreur(s)</strong>' +
                        '<div class="result-details">' +
                            errorMessages.slice(0, 5).join('<br>') +
                            (errorMessages.length > 5 ? '<br>... et ' + (errorMessages.length - 5) + ' autre(s) erreur(s)' : '') +
                            '<br><br>' +
                            '<a href="table_autres.php" class="btn primary">Voir le tableau</a>' +
                            '<button class="btn green" onclick="resetAll()" style="margin-left:8px;">Réessayer</button>' +
                        '</div>';
                }
            }, 800);
        }

        // ── Reset ──
        function resetAll() {
            parsedData = [];
            excelHeaders = [];
            fileInput.value = '';
            fileInfo.classList.remove('show');
            dropZone.style.display = '';
            mappingSection.classList.remove('show');
            previewSection.classList.remove('show');
            importActions.style.display = 'none';
            importProgress.classList.remove('show');
            importResult.classList.remove('show');
            progressBar.style.width = '0%';
        }

        // ── Helpers ──
        function formatBytes(b) {
            if (b < 1024) return b + ' o';
            if (b < 1048576) return (b / 1024).toFixed(1) + ' Ko';
            return (b / 1048576).toFixed(1) + ' Mo';
        }

        function escapeHtml(s) {
            const div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }
    </script>
</body>
</html>
