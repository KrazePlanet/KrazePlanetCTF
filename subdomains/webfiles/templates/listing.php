<?php // templates/listing.php ?>
<div id="dynamic-content">
    <div class="row mb-3 align-items-center g-2">
        <div class="col-auto d-flex align-items-center">
            <span class="text-muted small"><?= htmlspecialchars($L['nav']['show']) ?></span>
            <select id="perPage" class="form-select form-select-sm d-inline-block w-auto mx-2">
                <option value="20"  <?= $perPageReq == '20'  ? 'selected' : '' ?>>20</option>
                <option value="40"  <?= $perPageReq == '40'  ? 'selected' : '' ?>>40</option>
                <option value="100" <?= $perPageReq == '100' ? 'selected' : '' ?>>100</option>
                <option value="all" <?= $perPageReq == 'all' ? 'selected' : '' ?>><?= htmlspecialchars($L['nav']['show_all']) ?></option>
            </select>
        </div>

        <!-- Search block: shown only if enable_search = true -->
        <div class="col">
            <?php if ($isSearchEnabled): ?>
            <div class="input-group input-group-sm" style="max-width: 400px;">
                <input type="search" id="searchInput" class="form-control"
                       placeholder="<?= htmlspecialchars($L['nav']['search_placeholder']) ?>"
                       value="<?= htmlspecialchars($searchQuery) ?>">
                <button class="btn btn-primary" type="button" id="searchBtn"><?= htmlspecialchars($L['nav']['search_btn']) ?></button>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary btn-sm d-none" id="btn-audioplayer">
                <i class="fa-solid fa-play me-1"></i> <?= htmlspecialchars($L['toolbar']['listen']) ?>
            </button>
        </div>
        <?php if ($isUploadEnabled && !$isSearch): ?>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-upload"
                    data-dir="<?= htmlspecialchars($currentRel) ?>"
                    data-max-size="<?= htmlspecialchars($config['max_upload_size'] ?? '') ?>">
                <i class="fa-solid fa-upload me-1"></i> <?= htmlspecialchars($L['toolbar']['upload']) ?>
            </button>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-create-folder"
                    data-dir="<?= htmlspecialchars($currentRel) ?>">
                <i class="fa-solid fa-folder-plus me-1"></i> <?= htmlspecialchars($L['toolbar']['create_folder']) ?>
            </button>
        </div>
        <?php endif; ?>
        <?php if ($isDownloadEnabled): ?>
        <div class="col-auto">
            <button type="submit" form="main-form" class="btn btn-success btn-sm" id="btn-zip" disabled>
                <i class="fa-solid fa-download me-1"></i> <?= htmlspecialchars($L['toolbar']['download_selected']) ?> (<span id="count">0</span>)
            </button>
        </div>
        <?php endif; ?>
        <?php if ($isDeleteEnabled): ?>
        <div class="col-auto">
            <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected" disabled>
                <i class="fa-solid fa-trash-can me-1"></i> <?= htmlspecialchars($L['toolbar']['delete_selected']) ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
    <nav class="mb-3">
        <div class="small p-2 rounded shadow-sm border d-inline-block" style="background-color:var(--fm-breadcrumb-bg);color:var(--fm-text);">
            <a href="?dir=" class="dir-link text-decoration-none" data-dir=""><i class="fa-solid fa-house-user me-1"></i><?= htmlspecialchars($L['nav']['root']) ?></a>
            <?php
            $cumPath = '';
            foreach (array_filter(explode(DIRECTORY_SEPARATOR, $currentRel)) as $part):
                $cumPath .= ($cumPath ? DIRECTORY_SEPARATOR : '') . $part;
            ?>
            <span class="text-muted mx-1">/</span>
            <a href="?dir=<?= urlencode($cumPath) ?>" class="dir-link text-decoration-none fw-bold" data-dir="<?= htmlspecialchars($cumPath) ?>"><?= htmlspecialchars($part) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
    <form id="main-form" method="POST" action="index.php">
        <input type="hidden" name="action" value="download_selected">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($currentRel) ?>">

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color:var(--fm-table-head-bg);">
                        <tr>
                            <th width="40" class="ps-3"><input type="checkbox" class="form-check-input" id="check-all"></th>
                            <th class="sort-th" data-sort="name" style="cursor:pointer;color:var(--fm-link);" title="<?= htmlspecialchars($L['table']['sort_name']) ?>">
                                <?= htmlspecialchars($L['table']['col_name']) ?> <?= $sortBy === 'name' ? ($sortOrder === 'asc' ? '↑' : '↓') : '<span class="text-muted opacity-50">↕</span>' ?>
                            </th>
                            <th style="color:var(--fm-link);"><?= htmlspecialchars($L['table']['col_path']) ?></th>
                            <th width="120" class="text-end sort-th" data-sort="size" style="cursor:pointer;color:var(--fm-link);" title="<?= htmlspecialchars($L['table']['sort_size']) ?>">
                                <?= htmlspecialchars($L['table']['col_size']) ?> <?= $sortBy === 'size' ? ($sortOrder === 'asc' ? '↑' : '↓') : '<span class="text-muted opacity-50">↕</span>' ?>
                            </th>
                            <th width="180" class="text-end pe-3 sort-th" data-sort="mtime" style="cursor:pointer;color:var(--fm-link);" title="<?= htmlspecialchars($L['table']['sort_date']) ?>">
                                <?= htmlspecialchars($L['table']['col_date']) ?> <?= $sortBy === 'mtime' ? ($sortOrder === 'asc' ? '↑' : '↓') : '<span class="text-muted opacity-50">↕</span>' ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted"><?= htmlspecialchars($L['table']['no_files']) ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="ps-3">
                                <input type="checkbox" name="selected[]" value="<?= htmlspecialchars($item['rel_path']) ?>"
                                       class="form-check-input file-check" <?= $item['is_dir'] ? 'disabled' : '' ?>>
                            </td>
                            <td>
                                <i class="<?= $item['icon'] ?> me-2"></i>
                                <?php if ($item['is_dir']): ?>
                                    <a href="?dir=<?= urlencode($item['rel_path']) ?>" class="dir-link text-primary text-decoration-none fw-bold" data-dir="<?= htmlspecialchars($item['rel_path']) ?>">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                <?php else: ?>
                                <?php
                                $imgExts = ['jpg','jpeg','png','gif','webp','avif','bmp','ico','svg'];
                                $isImg   = in_array(strtolower($item['ext']), $imgExts);
                                ?>
                                    <a href="?download=<?= urlencode($item['rel_path']) ?>" class="preview-link text-decoration-none"
                                       style="color:var(--fm-text);"
                                       data-file="<?= htmlspecialchars($item['rel_path']) ?>" data-type="<?= htmlspecialchars($item['ext']) ?>"
                                       <?= $isImg ? 'data-thumb="' . htmlspecialchars($item['rel_path']) . '"' : '' ?>>
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?php
                                $rawPath  = str_replace('\\', '/', $item['rel_path']);
                                $pathOnly = (strpos($rawPath, '/') !== false) ? dirname($rawPath) : '';
                                $dirLabel = ($pathOnly === '' || $pathOnly === '.') ? '/' : '/' . trim($pathOnly, '/') . '/';
                                if ($isSearch):
                                    $dirValue = ($pathOnly === '' || $pathOnly === '.') ? '' : trim($pathOnly, '/');
                                ?>
                                <a href="?dir=<?= urlencode($dirValue) ?>"
                                   class="dir-link text-muted text-decoration-none path-link"
                                   data-dir="<?= htmlspecialchars($dirValue) ?>"
                                   title="<?= htmlspecialchars($L['table']['go_to_folder']) ?>"><?= $dirLabel ?></a>
                                <?php else: ?>
                                <?= $dirLabel ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-muted small"><?= $item['size_str'] ?></td>
                            <td class="text-end text-muted small pe-3">
                                <span class="d-inline-flex align-items-center gap-2 justify-content-end">
                                    <?= $item['mtime_str'] ?>
                                    <?php if ($isUploadEnabled && !$item['is_dir']): ?>
                                    <button type="button"
                                            class="btn btn-link p-0 text-secondary btn-rename-file"
                                            data-file="<?= htmlspecialchars($item['rel_path']) ?>"
                                            data-name="<?= htmlspecialchars($item['name']) ?>"
                                            title="<?= htmlspecialchars($L['table']['rename_file']) ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php elseif ($isUploadEnabled && $item['is_dir']): ?>
                                    <button type="button"
                                            class="btn btn-link p-0 text-secondary btn-rename-file"
                                            data-file="<?= htmlspecialchars($item['rel_path']) ?>"
                                            data-name="<?= htmlspecialchars($item['name']) ?>"
                                            data-is-dir="1"
                                            title="<?= htmlspecialchars($L['table']['rename_folder']) ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($isDeleteEnabled && !$item['is_dir']): ?>
                                    <button type="button"
                                            class="btn btn-link p-0 text-danger btn-delete-file"
                                            data-file="<?= htmlspecialchars($item['rel_path']) ?>"
                                            data-name="<?= htmlspecialchars($item['name']) ?>"
                                            title="<?= htmlspecialchars($L['table']['delete_file']) ?>">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    <div class="mt-4 d-flex justify-content-between align-items-center small text-muted">
        <div><?= htmlspecialchars(str_replace(['{from}','{to}','{total}'], [$from, $to, $total], $L['nav']['displayed'])) ?></div>
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="#" data-page="<?= max(1, $page - 1) ?>"><?= htmlspecialchars($L['nav']['prev']) ?></a>
                </li>
                <?php
                $side_pages = 2;
                if ($page > ($side_pages + 1)):
                ?>
                    <li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>
                    <?php if ($page > ($side_pages + 2)): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>
                <?php
                $start = max(1, $page - $side_pages);
                $end   = min($totalPages, $page + $side_pages);
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < ($totalPages - $side_pages)): ?>
                    <?php if ($page < ($totalPages - $side_pages - 1)): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item"><a class="page-link" href="#" data-page="<?= $totalPages ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="#" data-page="<?= min($totalPages, $page + 1) ?>"><?= htmlspecialchars($L['nav']['next']) ?></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>