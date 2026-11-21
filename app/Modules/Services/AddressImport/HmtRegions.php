<?php

declare(strict_types=1);

namespace App\Modules\Services\AddressImport;

/**
 * 港澳台行政区划（带民政部标准编码）
 *
 * Gitee 远端数据源中港澳台为主文件之外的独立文件（且无编码），
 * 这里以代码内置的权威编码数据为准，保证 area_code 可归一化。
 */
final class HmtRegions
{
    /**
     * 省级编码列表
     *
     * @return array<int, string>
     */
    public static function unitKeys(): array
    {
        return ['81', '82', '71'];
    }

    /**
     * 顶层数据（省 -> 市 -> 区，含民政部编码），供 Seeder 等复用
     *
     * @return array<string, array{code: string, name: string, children: array}>
     */
    public static function items(): array
    {
        return self::tree();
    }

    /**
     * 指定单位的平铺行（省级/市/区 三级）
     *
     * @return array<int, array{code: string, name: string, level: string, level_num: int, parent_code: ?string, merge_path: array<int, string>}>
     */
    public static function rows(string $unitKey): array
    {
        $unit = self::tree()[$unitKey] ?? null;

        if ($unit === null) {
            return [];
        }

        $rows = [];

        foreach (self::flatten($unit, null, []) as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private static function tree(): array
    {
        $items = [
            '81' => [
                'code' => '81',
                'name' => '香港特别行政区',
                'children' => [
                    ['code' => '8100', 'name' => '香港岛', 'children' => [
                        ['code' => '810001', 'name' => '中西区'],
                        ['code' => '810002', 'name' => '湾仔区'],
                        ['code' => '810003', 'name' => '东区'],
                        ['code' => '810004', 'name' => '南区'],
                    ]],
                    ['code' => '8101', 'name' => '九龙', 'children' => [
                        ['code' => '810101', 'name' => '油尖旺区'],
                        ['code' => '810102', 'name' => '深水埗区'],
                        ['code' => '810103', 'name' => '九龙城区'],
                        ['code' => '810104', 'name' => '黄大仙区'],
                        ['code' => '810105', 'name' => '观塘区'],
                    ]],
                    ['code' => '8102', 'name' => '新界', 'children' => [
                        ['code' => '810201', 'name' => '葵青区'],
                        ['code' => '810202', 'name' => '荃湾区'],
                        ['code' => '810203', 'name' => '屯门区'],
                        ['code' => '810204', 'name' => '元朗区'],
                        ['code' => '810205', 'name' => '北区'],
                        ['code' => '810206', 'name' => '大埔区'],
                        ['code' => '810207', 'name' => '沙田区'],
                        ['code' => '810208', 'name' => '西贡区'],
                        ['code' => '810209', 'name' => '离岛区'],
                    ]],
                ],
            ],
            '82' => [
                'code' => '82',
                'name' => '澳门特别行政区',
                'children' => [
                    ['code' => '8200', 'name' => '澳门半岛', 'children' => [
                        ['code' => '820001', 'name' => '花地玛堂区'],
                        ['code' => '820002', 'name' => '花王堂区'],
                        ['code' => '820003', 'name' => '望德堂区'],
                        ['code' => '820004', 'name' => '大堂区'],
                        ['code' => '820005', 'name' => '风顺堂区'],
                    ]],
                    ['code' => '8201', 'name' => '氹仔', 'children' => [
                        ['code' => '820101', 'name' => '嘉模堂区'],
                    ]],
                    ['code' => '8202', 'name' => '路环', 'children' => [
                        ['code' => '820201', 'name' => '圣方济各堂区'],
                    ]],
                ],
            ],
            '71' => [
                'code' => '71',
                'name' => '台湾省',
                'children' => [
                    ['code' => '7101', 'name' => '台北市', 'children' => [
                        ['code' => '710101', 'name' => '中正区'],
                        ['code' => '710102', 'name' => '大同区'],
                        ['code' => '710103', 'name' => '中山区'],
                        ['code' => '710104', 'name' => '松山区'],
                        ['code' => '710105', 'name' => '大安区'],
                        ['code' => '710106', 'name' => '万华区'],
                        ['code' => '710107', 'name' => '信义区'],
                        ['code' => '710108', 'name' => '士林区'],
                        ['code' => '710109', 'name' => '北投区'],
                        ['code' => '710110', 'name' => '内湖区'],
                        ['code' => '710111', 'name' => '南港区'],
                        ['code' => '710112', 'name' => '文山区'],
                    ]],
                    ['code' => '7102', 'name' => '新北市', 'children' => [
                        ['code' => '710201', 'name' => '板桥区'],
                        ['code' => '710202', 'name' => '三重区'],
                        ['code' => '710203', 'name' => '中和区'],
                        ['code' => '710204', 'name' => '永和区'],
                        ['code' => '710205', 'name' => '新庄区'],
                        ['code' => '710206', 'name' => '新店区'],
                        ['code' => '710207', 'name' => '土城区'],
                        ['code' => '710208', 'name' => '芦洲区'],
                        ['code' => '710209', 'name' => '汐止区'],
                    ]],
                    ['code' => '7103', 'name' => '桃园市', 'children' => [
                        ['code' => '710301', 'name' => '桃园区'],
                        ['code' => '710302', 'name' => '中壢区'],
                        ['code' => '710303', 'name' => '平镇区'],
                        ['code' => '710304', 'name' => '八德区'],
                        ['code' => '710305', 'name' => '杨梅区'],
                        ['code' => '710306', 'name' => '芦竹区'],
                    ]],
                    ['code' => '7104', 'name' => '台中市', 'children' => [
                        ['code' => '710401', 'name' => '中区'],
                        ['code' => '710402', 'name' => '东区'],
                        ['code' => '710403', 'name' => '南区'],
                        ['code' => '710404', 'name' => '西区'],
                        ['code' => '710405', 'name' => '北区'],
                        ['code' => '710406', 'name' => '西屯区'],
                        ['code' => '710407', 'name' => '南屯区'],
                        ['code' => '710408', 'name' => '北屯区'],
                    ]],
                    ['code' => '7105', 'name' => '台南市', 'children' => [
                        ['code' => '710501', 'name' => '中西区'],
                        ['code' => '710502', 'name' => '东区'],
                        ['code' => '710503', 'name' => '南区'],
                        ['code' => '710504', 'name' => '北区'],
                        ['code' => '710505', 'name' => '安平区'],
                        ['code' => '710506', 'name' => '安南区'],
                    ]],
                    ['code' => '7106', 'name' => '高雄市', 'children' => [
                        ['code' => '710601', 'name' => '楠梓区'],
                        ['code' => '710602', 'name' => '左营区'],
                        ['code' => '710603', 'name' => '鼓山区'],
                        ['code' => '710604', 'name' => '三民区'],
                        ['code' => '710605', 'name' => '盐埕区'],
                        ['code' => '710606', 'name' => '前金区'],
                        ['code' => '710607', 'name' => '新兴区'],
                        ['code' => '710608', 'name' => '苓雅区'],
                        ['code' => '710609', 'name' => '前镇区'],
                        ['code' => '710610', 'name' => '小港区'],
                    ]],
                    ['code' => '7107', 'name' => '基隆市', 'children' => [
                        ['code' => '710701', 'name' => '仁爱区'],
                        ['code' => '710702', 'name' => '信义区'],
                        ['code' => '710703', 'name' => '中正区'],
                        ['code' => '710704', 'name' => '中山区'],
                        ['code' => '710705', 'name' => '安乐区'],
                        ['code' => '710706', 'name' => '暖暖区'],
                        ['code' => '710707', 'name' => '七堵区'],
                    ]],
                    ['code' => '7108', 'name' => '新竹市', 'children' => [
                        ['code' => '710801', 'name' => '东区'],
                        ['code' => '710802', 'name' => '北区'],
                        ['code' => '710803', 'name' => '香山区'],
                    ]],
                    ['code' => '7109', 'name' => '嘉义市', 'children' => [
                        ['code' => '710901', 'name' => '东区'],
                        ['code' => '710902', 'name' => '西区'],
                    ]],
                ],
            ],
        ];

        return $items;
    }

    /**
     * @return array<int, array{code: string, name: string, level: string, level_num: int, parent_code: ?string, merge_path: array<int, string>}>
     */
    private static function flatten(array $node, ?string $parentCode, array $path): array
    {
        $codeLen = strlen($node['code']);
        $levelNum = match (true) {
            $codeLen <= 2 => 2,
            $codeLen <= 4 => 3,
            $codeLen <= 6 => 4,
            default => 5,
        };
        $level = match ($levelNum) {
            2 => 'province',
            3 => 'city',
            4 => 'district',
            default => 'township',
        };

        $currentPath = array_merge($path, [$node['name']]);

        $rows = [[
            'code' => $node['code'],
            'name' => $node['name'],
            'level' => $level,
            'level_num' => $levelNum,
            'parent_code' => $parentCode,
            'merge_path' => $currentPath,
        ]];

        foreach ($node['children'] ?? [] as $child) {
            $rows = array_merge($rows, self::flatten($child, $node['code'], $currentPath));
        }

        return $rows;
    }
}
