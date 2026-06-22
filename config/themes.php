<?php

/**
 * Preset tema warna. Tiap preset menimpa CSS variable --color-* (lihat
 * resources/css/app.css @theme). Diterapkan di layout via <style>:root{...}.
 * Default 'tanah-hangat' = palet Aterra asli (tampilan tidak berubah).
 */
return [

    'default' => 'tanah-hangat',

    'presets' => [

        'tanah-hangat' => [
            'label' => 'Tanah Hangat',
            'swatch' => '#B0552F',
            'tokens' => [
                'bg' => '#ECE6DC', 'header' => '#F7F3EC', 'panel' => '#FBF8F2', 'panel-2' => '#F2EDE5',
                'line' => '#E6DFD2', 'line-2' => '#E2DACB',
                'sidebar' => '#201B17', 'sidebar-ink' => '#CFC6B8', 'sidebar-muted' => '#857B6C',
                'accent' => '#B0552F', 'accent-soft' => '#C9A892',
                'ink' => '#2A241F', 'ink-strong' => '#241F1A', 'ink-muted' => '#8C8174',
            ],
        ],

        'hutan' => [
            'label' => 'Hutan',
            'swatch' => '#2F8F5B',
            'tokens' => [
                'bg' => '#ECF1EE', 'header' => '#F2F7F4', 'panel' => '#FBFDFB', 'panel-2' => '#EEF3F0',
                'line' => '#DCE6E0', 'line-2' => '#D3E0D8',
                'sidebar' => '#14241D', 'sidebar-ink' => '#C5D6CD', 'sidebar-muted' => '#7E9387',
                'accent' => '#2F8F5B', 'accent-soft' => '#A7CDB7',
                'ink' => '#1E2A24', 'ink-strong' => '#18221C', 'ink-muted' => '#75857C',
            ],
        ],

        'samudra' => [
            'label' => 'Samudra',
            'swatch' => '#2F6FB0',
            'tokens' => [
                'bg' => '#E9EEF4', 'header' => '#F1F5FA', 'panel' => '#FBFCFE', 'panel-2' => '#EDF2F7',
                'line' => '#DBE3EC', 'line-2' => '#D2DCE7',
                'sidebar' => '#16202E', 'sidebar-ink' => '#C5D2E0', 'sidebar-muted' => '#7C8A9C',
                'accent' => '#2F6FB0', 'accent-soft' => '#A8C3DE',
                'ink' => '#1E2733', 'ink-strong' => '#182029', 'ink-muted' => '#74808F',
            ],
        ],

        'senja' => [
            'label' => 'Senja',
            'swatch' => '#6D4FB0',
            'tokens' => [
                'bg' => '#EEEBF3', 'header' => '#F5F2F9', 'panel' => '#FCFBFE', 'panel-2' => '#F0EDF5',
                'line' => '#E2DCEC', 'line-2' => '#DAD2E7',
                'sidebar' => '#211B2C', 'sidebar-ink' => '#D0C8DC', 'sidebar-muted' => '#877C97',
                'accent' => '#6D4FB0', 'accent-soft' => '#C0B0DE',
                'ink' => '#251F2E', 'ink-strong' => '#1F1A28', 'ink-muted' => '#80768C',
            ],
        ],

        'monokrom' => [
            'label' => 'Monokrom',
            'swatch' => '#475569',
            'tokens' => [
                'bg' => '#ECEEF1', 'header' => '#F4F6F8', 'panel' => '#FCFCFD', 'panel-2' => '#EFF1F4',
                'line' => '#E0E4E9', 'line-2' => '#D7DCE2',
                'sidebar' => '#1C2025', 'sidebar-ink' => '#CBD0D6', 'sidebar-muted' => '#828B95',
                'accent' => '#475569', 'accent-soft' => '#B7C0CB',
                'ink' => '#20242A', 'ink-strong' => '#1A1D22', 'ink-muted' => '#79818B',
            ],
        ],

        'delima' => [
            'label' => 'Delima',
            'swatch' => '#C0314A',
            'tokens' => [
                'bg' => '#F5EAEC', 'header' => '#FAF2F3', 'panel' => '#FEFCFC', 'panel-2' => '#F6ECEE',
                'line' => '#ECDCDF', 'line-2' => '#E6D2D6',
                'sidebar' => '#2A1619', 'sidebar-ink' => '#DCC8CC', 'sidebar-muted' => '#9A8186',
                'accent' => '#C0314A', 'accent-soft' => '#E0AAB4',
                'ink' => '#2A1F22', 'ink-strong' => '#221A1C', 'ink-muted' => '#8C7479',
            ],
        ],

    ],
];
