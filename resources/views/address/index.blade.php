<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>地址信息</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-blue-600 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 py-6">
                <h1 class="text-2xl font-bold">&#128205; 地址信息管理系统</h1>
                <p class="text-blue-200 mt-1">
                    {{ $total }} 条数据 · {{ $provinceCount }} 个省级 · {{ $cityCount }} 个地级 · {{ $districtCount }} 个县级
                </p>
            </div>
        </header>
        <main class="max-w-5xl mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex gap-3">
                    <input type="text" id="searchInput" placeholder="输入地址名称或代码搜索..."
                        class="border border-gray-300 rounded-lg px-4 py-2 text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button onclick="doSearch()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">
                        &#128269; 搜索
                    </button>
                    <button onclick="resetSearch()"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium">
                        重置
                    </button>
                </div>
                <p id="searchHint" class="text-xs text-gray-400 mt-2">默认显示省级行政区，点击可展开查看下级</p>
            </div>
            <div id="addressTree" class="space-y-4"></div>
        </main>
        <script>
        var provinceData = @json($provinces);

        function renderTree(nodes, depth) {
            if (!nodes || nodes.length === 0) return '';
            var html = '';
            for (var i = 0; i < nodes.length; i++) {
                var node = nodes[i];
                var hasChildren = node.children && node.children.length > 0;
                var levelLabels = ['省级', '地级', '县级'];
                var levelLabel = levelLabels[depth] || '';
                var icon = hasChildren ? '&#9660;' : '&#9679;';
                html += '<div class="rounded-lg border border-gray-200 overflow-hidden">';
                html += '<div class="flex items-center px-4 py-3 cursor-pointer hover:bg-gray-50" onclick="toggleChildren(this)">';
                html += '<span class="text-xs mr-2 opacity-50">[' + levelLabel + ']</span>';
                html += '<span class="flex-1 font-medium ' + (depth === 0 ? 'text-lg' : 'text-base') + '">';
                html += '<span class="mr-2">' + icon + '</span>' + node.name;
                html += '</span>';
                html += '<span class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-0.5 rounded">' + node.code + '</span>';
                html += '</div>';
                if (hasChildren) {
                    html += '<div class="children-container" style="display:none; background:#f9fafb">';
                    html += renderTree(node.children, depth + 1);
                    html += '</div>';
                }
                html += '</div>';
            }
            return html;
        }

        function toggleChildren(el) {
            var container = el.nextElementSibling;
            var iconSpan = el.querySelectorAll('span')[1];
            if (container.style.display === 'none') {
                container.style.display = 'block';
                if (iconSpan) iconSpan.innerHTML = '&#9660;';
            } else {
                container.style.display = 'none';
                if (iconSpan) iconSpan.innerHTML = '&#9654;';
            }
        }

        function resetSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('addressTree').innerHTML = renderTree(provinceData, 0);
            document.getElementById('searchHint').textContent = '默认显示省级行政区，点击可展开查看下级';
        }

        function getAllFlat(nodes, depth) {
            var results = [];
            for (var i = 0; i < nodes.length; i++) {
                var node = nodes[i];
                results.push({name: node.name, code: node.code, depth: depth});
                if (node.children && node.children.length > 0) {
                    var children = getAllFlat(node.children, depth + 1);
                    results = results.concat(children);
                }
            }
            return results;
        }

        function findParentChain(nodes, targetCode, parentChain) {
            for (var i = 0; i < nodes.length; i++) {
                var node = nodes[i];
                var currentChain = parentChain ? parentChain.concat([node]) : [node];
                if (node.code === targetCode) return currentChain;
                if (node.children && node.children.length > 0) {
                    var found = findParentChain(node.children, targetCode, currentChain);
                    if (found) return found;
                }
            }
            return null;
        }

        function buildResultFromChain(chain) {
            if (!chain || chain.length === 0) return null;
            var result = {
                id: chain[0].id,
                name: chain[0].name,
                code: chain[0].code,
                children: []
            };
            if (chain.length > 1) {
                var child = buildResultFromChain(chain.slice(1));
                if (child) result.children.push(child);
            }
            return result;
        }

        function doSearch() {
            var keyword = document.getElementById('searchInput').value.trim();
            if (!keyword) { resetSearch(); return; }
            var allFlat = getAllFlat(provinceData, 0);
            var matchedCodes = [];
            for (var i = 0; i < allFlat.length; i++) {
                if (allFlat[i].name.indexOf(keyword) !== -1 || allFlat[i].code.indexOf(keyword) !== -1) {
                    matchedCodes.push(allFlat[i].code);
                }
            }
            var seen = {};
            var results = [];
            for (var j = 0; j < matchedCodes.length; j++) {
                var chain = findParentChain(provinceData, matchedCodes[j], null);
                if (chain) {
                    var rootCode = chain[0].code;
                    if (!seen[rootCode]) {
                        seen[rootCode] = true;
                        var result = buildResultFromChain(chain);
                        if (result) results.push(result);
                    }
                }
            }
            renderSearchResults(results);
            document.getElementById('searchHint').textContent = '搜索结果（共 ' + matchedCodes.length + ' 条命中，含所有上级地址）';
        }

        function renderSearchResults(nodes) {
            if (nodes.length === 0) {
                document.getElementById('addressTree').innerHTML = '<div class="bg-white rounded-lg shadow p-8 text-center text-gray-500"><p class="text-lg">未找到匹配的结果</p></div>';
                return;
            }
            var html = '<h2 class="text-lg font-bold mb-4">搜索结果</h2><div class="space-y-2">';
            for (var i = 0; i < nodes.length; i++) {
                html += renderSearchNode(nodes[i], 0);
            }
            html += '</div>';
            document.getElementById('addressTree').innerHTML = html;
        }

        function renderSearchNode(node, depth) {
            var hasChildren = node.children && node.children.length > 0;
            var levelLabels = ['省级', '地级', '县级'];
            var levelLabel = levelLabels[depth] || '';
            var icon = hasChildren ? '&#9660;' : '&#9679;';
            var html = '<div class="rounded-lg border border-blue-200 bg-blue-50 overflow-hidden">';
            html += '<div class="flex items-center px-4 py-3 cursor-pointer hover:bg-blue-100" onclick="toggleSearchChildren(this)">';
            html += '<span class="text-xs mr-2 opacity-50">[' + levelLabel + ']</span>';
            html += '<span class="flex-1 font-medium text-base"><span class="mr-2">' + icon + '</span>' + node.name + '</span>';
            html += '<span class="text-xs font-mono text-blue-600 bg-blue-100 px-2 py-0.5 rounded">' + node.code + '</span>';
            html += '</div>';
            if (hasChildren) {
                html += '<div class="children-container" style="display:none; background:#eff6ff">';
                for (var j = 0; j < node.children.length; j++) {
                    html += renderSearchNode(node.children[j], depth + 1);
                }
                html += '</div>';
            }
            html += '</div>';
            return html;
        }

        function toggleSearchChildren(el) {
            var container = el.nextElementSibling;
            var iconSpan = el.querySelectorAll('span')[1];
            if (container.style.display === 'none') {
                container.style.display = 'block';
                if (iconSpan) iconSpan.innerHTML = '&#9660;';
            } else {
                container.style.display = 'none';
                if (iconSpan) iconSpan.innerHTML = '&#9654;';
            }
        }

        document.getElementById('addressTree').innerHTML = renderTree(provinceData, 0);
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') doSearch();
        });
        </script>
    </div>
</body>
</html>
