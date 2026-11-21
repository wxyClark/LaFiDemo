<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL 查询工具</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .compact-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        @media (max-width: 768px) {
            .responsive-form {
                flex-direction: column;
            }

            .responsive-form > div {
                width: 100% !important;
            }

            .responsive-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .responsive-actions > div {
                width: 100%;
            }
        }

        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #d1d5db, transparent);
            margin: 2rem 0;
        }

        .dark .divider {
            background: linear-gradient(to right, transparent, #4b5563, transparent);
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 0.5rem;
        }

        .dark .loading-overlay {
            background-color: rgba(0, 0, 0, 0.7);
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        .dark .spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left-color: #3b82f6;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
<x-app-layout>


<!-- 导航栏 -->
<nav class="bg-white dark:bg-gray-800 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <i class="fas fa-database text-blue-500 text-xl mr-2"></i>
                            <span class="font-semibold text-xl text-gray-800 dark:text-white">SQL Query Tool</span>
                        </div>
                    </div>
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Execute secure SELECT queries and show the results</p>
            </div>
        </div>
    </div>
</nav>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 标题区域 -->


        <!-- 主内容区域 -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <!-- SQL 查询表单 -->
                <form id="sql-form" method="POST">
                    @csrf
                    <div class="mb-6 relative">
                        <div class="flex responsive-form gap-4">
                            <!-- SQL 语句输入框 - 占大部分宽度 -->
                            <div class="flex-1">
                                <label for="sql" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SQL</label>
                                <textarea
                                    id="sql"
                                    name="sql"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-3 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                    rows="6"
                                    placeholder="Please input SELECT queries here.
For example：SELECT id, name, email FROM users limit 10"
                                    required
                                    autofocus
                                ></textarea>

                                <div id="sql-error" class="mt-1 text-sm text-red-600 hidden"></div>
                            </div>

                            <!-- 右侧控件区域 -->
                            <div class="w-48 flex flex-col">
                                <!-- 分页数量选择 - 与SQL输入框上边缘对齐 -->
                                <div class="mb-4">
                                    <label for="page_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Every page show</label>
                                    <div class="relative">
                                        <select
                                            id="page_size"
                                            name="page_size"
                                            class="compact-select w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-3 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        >
                                            <option value="10">10 records</option>
                                            <option value="20">20 records</option>
                                            <option value="50">50 records</option>
                                            <option value="100">100 records</option>
                                        </select>
                                    </div>
                                    <div id="page-size-error" class="mt-1 text-sm text-red-600 hidden"></div>
                                </div>

                                <!-- 执行按钮 - 与SQL输入框下边缘对齐 -->
                                <div class="mt-auto">
                                    <button type="submit" id="execute-btn" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                                        <i class="fas fa-play mr-2"></i> Execute
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 加载遮罩层 -->
                        <div id="loading-overlay" class="loading-overlay hidden">
                            <div class="text-center">
                                <div class="spinner mb-4"></div>
                                <p class="text-gray-700 dark:text-gray-300 font-medium">执行中...</p>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- 分割线 -->
                <div class="divider"></div>

                <!-- 查询结果展示区域 -->
                <div id="results-section" class="hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 responsive-actions">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 sm:mb-0">Result</h3>

                        <!-- 导出按钮 -->
                        <div class="flex space-x-2">
                            <button id="export-excel" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-file-excel mr-2"></i> Export Excel
                            </button>

                            <button id="export-json" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-file-code mr-2"></i> Export JSON
                            </button>
                        </div>
                    </div>

                    <!-- 分页信息 -->
                    <div id="pagination-info" class="mb-4 text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                        <!-- 分页信息将通过JavaScript填充 -->
                    </div>

                    <!-- 数据表格 -->
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm table-container">
                        <table id="results-table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- 表格内容将通过JavaScript填充 -->
                        </table>
                    </div>

                    <!-- 分页导航 -->
                    <div id="pagination-nav" class="mt-6 flex items-center justify-between">
                        <!-- 分页导航将通过JavaScript填充 -->
                    </div>
                </div>

                <!-- 空结果提示 -->
                <div id="empty-results" class="hidden mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                    <p class="text-yellow-800 dark:text-yellow-200 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Execute successful, but no results found.
                    </p>
                </div>

                <!-- SQL错误 -->
                <div id="query-error" class="hidden mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                    <p class="text-yellow-800 dark:text-yellow-200 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Execute successful, but no results found.
                    </p>
                </div>

                <!-- 初始状态提示 -->
                <div id="initial-state" class="mt-8 p-4 bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800 rounded-md">
                    <p class="text-gray-600 dark:text-gray-400 flex items-center justify-center">
                        <i class="fas fa-database mr-2"></i> Please input SELECT queries here.
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>
</x-app-layout>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sqlForm = document.getElementById('sql-form');
        const executeBtn = document.getElementById('execute-btn');
        const loadingOverlay = document.getElementById('loading-overlay');
        const resultsSection = document.getElementById('results-section');
        const emptyResults = document.getElementById('empty-results');
        const initialState = document.getElementById('initial-state');
        const queryError = document.getElementById('query-error');
        const exportExcelBtn = document.getElementById('export-excel');
        const exportJsonBtn = document.getElementById('export-json');

        // 当前查询参数
        let currentSql = '';
        let currentPageSize = 10;
        let currentPage = 1;

        // 表单提交事件
        sqlForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // 隐藏之前的错误提示
            document.getElementById('query-error').classList.add('hidden');

            // 获取表单数据
            const formData = new FormData(sqlForm);
            currentSql = formData.get('sql');
            currentPageSize = parseInt(formData.get('page_size'));
            currentPage = 0;

            // 验证SQL语句是否为SELECT
            const sql = currentSql.trim().toUpperCase();
            if (!sql.startsWith('SELECT')) {
                alert('Only SELECT statement is supported!');
                document.getElementById('sql').focus();
                return false;
            }

            // 显示加载状态
            executeBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> In progressing ...';
            executeBtn.disabled = true;
            loadingOverlay.classList.remove('hidden');

            // 隐藏初始状态
            initialState.classList.add('hidden');

            // 发送AJAX请求
            fetch('/query/list', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sql: currentSql,
                    page_size: currentPageSize,
                    page: currentPage
                })
            })
                .then(response => {
                    // 访问频次限制
                    if (response.status === 429) {
                        throw new Error('The request is too frequent. Please try again later!');
                    }
                    if (!response.ok) {
                        throw new Error('Network response error');
                    }
                    return response.json();
                })
                .then(data => {
                    // 隐藏加载状态
                    loadingOverlay.classList.add('hidden');
                    executeBtn.innerHTML = '<i class="fas fa-play mr-2"></i> 执行查询';
                    executeBtn.disabled = false;

                    // 检查status字段
                    if (data.status === false) {
                        // 显示错误信息
                        document.querySelector('#query-error p').innerHTML = `            <i class="fas fa-exclamation-triangle mr-2"></i>
            ${data.error_msg || '查询失败'}        `;
                        document.getElementById('query-error').classList.remove('hidden');
                        resultsSection.classList.add('hidden');
                        emptyResults.classList.add('hidden');
                        return;
                    }

                    // 处理返回的数据
                    if (data.list && data.list.length > 0) {
                        displayResults(data);
                    } else {
                        showEmptyResults();
                    }
                })
                .catch(error => {
                    // 隐藏加载状态
                    loadingOverlay.classList.add('hidden');
                    executeBtn.innerHTML = '<i class="fas fa-play mr-2"></i> 执行查询';
                    executeBtn.disabled = false;

                    console.error('错误:', error);
                    alert('查询失败: ' + error.message);
                });
        });

        // 显示查询结果
        function displayResults(data) {
            // 显示结果区域
            resultsSection.classList.remove('hidden');
            emptyResults.classList.add('hidden');

            // 更新分页信息
            document.getElementById('pagination-info').innerHTML = `
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i> 共 ${data.total} 条记录，当前显示第 ${data.page} 页，每页 ${data.page_size} 条
                `;

            // 更新表格
            const table = document.getElementById('results-table');
            table.innerHTML = '';

            // 创建表头
            if (data.list.length > 0) {
                const headers = Object.keys(data.list[0]);
                const thead = document.createElement('thead');
                thead.className = 'bg-gray-50 dark:bg-gray-700 sticky top-0';

                let headerRow = '<tr>';
                headers.forEach(header => {
                    headerRow += `<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">${header}</th>`;
                });
                headerRow += '</tr>';
                thead.innerHTML = headerRow;
                table.appendChild(thead);

                // 创建表体
                const tbody = document.createElement('tbody');
                tbody.className = 'bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700';

                data.list.forEach(row => {
                    let rowHtml = '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">';
                    Object.values(row).forEach(value => {
                        rowHtml += `<td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">${value}</td>`;
                    });
                    rowHtml += '</tr>';
                    tbody.innerHTML += rowHtml;
                });

                table.appendChild(tbody);
            }

            // 更新分页导航
            updatePagination(data);
        }

        // 更新分页导航
        function updatePagination(data) {
            const paginationNav = document.getElementById('pagination-nav');

            if (data.total <= data.page_size) {
                paginationNav.innerHTML = '';
                return;
            }

            const startItem = (data.page - 1) * data.page_size + 1;
            const endItem = Math.min(data.page * data.page_size, data.total);

            let paginationHtml = `
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        显示 ${startItem} 到 ${endItem} 项，共 ${data.total} 项
                    </div>
                    <div class="flex space-x-2">
                `;

            if (data.page > 1) {
                paginationHtml += `
                        <button onclick="loadPage(${data.page - 1})" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i> 上一页
                        </button>
                    `;
            }

            if (data.page * data.page_size < data.total) {
                paginationHtml += `
                        <button onclick="loadPage(${data.page + 1})" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            下一页 <i class="fas fa-chevron-right ml-1"></i>
                        </button>
                    `;
            }

            paginationHtml += '</div>';
            paginationNav.innerHTML = paginationHtml;
        }

        // 加载指定页
        window.loadPage = function(page) {
            currentPage = page;

            // 显示加载状态
            loadingOverlay.classList.remove('hidden');

            // 发送AJAX请求
            fetch('/query/list', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sql: currentSql,
                    page_size: currentPageSize,
                    page: currentPage
                })
            })
                .then(response => {
                    // 访问频次限制
                    if (response.status === 429) {
                        throw new Error('The request is too frequent. Please try again later!');
                    }
                    if (!response.ok) {
                        throw new Error('Network response error');
                    }
                    return response.json();
                })
                .then(data => {
                    // 隐藏加载状态
                    loadingOverlay.classList.add('hidden');

                    // 处理返回的数据
                    if (data.list && data.list.length > 0) {
                        displayResults(data);
                    } else {
                        showEmptyResults();
                    }
                })
                .catch(error => {
                    // 隐藏加载状态
                    loadingOverlay.classList.add('hidden');
                    console.error('错误:', error);
                    alert('查询失败: ' + error.message);
                });
        };

        // 显示空结果
        function showEmptyResults() {
            resultsSection.classList.add('hidden');
            emptyResults.classList.remove('hidden');
        }

        // 导出Excel
        exportExcelBtn.addEventListener('click', function() {
            if (!currentSql) {
                alert('请先执行查询');
                return;
            }

            // 显示加载状态
            exportExcelBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Exporting...';
            exportExcelBtn.disabled = true;

            // 发送AJAX请求
            fetch('/query/export', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sql: currentSql,
                    type: 'excel'
                })
            })
                .then(response => {
                    // 访问频次限制
                    if (response.status === 429) {
                        throw new Error('The request is too frequent. Please try again later!');
                    }
                    if (!response.ok) {
                        throw new Error('Export failed');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // 生成包含时间戳的文件名
                    const now = new Date();
                    const timestamp = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}${String(now.getSeconds()).padStart(2, '0')}`;
                    const filename = `query_results_${timestamp}.xlsx`;

                    // 创建下载链接
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);

                    // 恢复按钮状态
                    exportExcelBtn.innerHTML = '<i class="fas fa-file-excel mr-2"></i> Export Excel';
                    exportExcelBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Export failed: ' + error.message);

                    // 恢复按钮状态
                    exportExcelBtn.innerHTML = '<i class="fas fa-file-excel mr-2"></i> Export Excel';
                    exportExcelBtn.disabled = false;
                });
        });

        // 导出JSON
        exportJsonBtn.addEventListener('click', function() {
            if (!currentSql) {
                alert('Please execute the query first');
                return;
            }

            // 显示加载状态
            exportJsonBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Exporting...';
            exportJsonBtn.disabled = true;

            // 发送AJAX请求
            fetch('/query/export', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sql: currentSql,
                    type: 'json'
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Export failed!');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // 生成包含时间戳的文件名
                    const now = new Date();
                    const timestamp = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}${String(now.getSeconds()).padStart(2, '0')}`;
                    const filename = `query_results_${timestamp}.json`;

                    // 创建下载链接
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);

                    // 恢复按钮状态
                    exportJsonBtn.innerHTML = '<i class="fas fa-file-code mr-2"></i> Export JSON';
                    exportJsonBtn.disabled = false;
                })
                .catch(error => {
                    console.error('错误:', error);
                    alert('Export failed: ' + error.message);

                    // 恢复按钮状态
                    exportJsonBtn.innerHTML = '<i class="fas fa-file-code mr-2"></i> Export JSON';
                    exportJsonBtn.disabled = false;
                });
        });
    });
</script>
</body>
</html>
