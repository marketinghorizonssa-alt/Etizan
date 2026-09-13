<?php
declare(strict_types=1);

function etizan_branch(string $cityEn): array {
    if ($cityEn === 'jeddah') {
        return [
            'name' => 'مكتب جدة',
            'phone' => '966559451110',
            'phone_display' => '055 945 1110',
            'address' => 'بلاتينيوم بلازا، المبنى الجنوبي، الدور الثاني، الرويس، مكتب 201، فيض السماء، جدة 23213',
            'map' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('شركة إتزان للمحاماة والاستشارات القانونية جدة بلاتينيوم بلازا'),
        ];
    }

    return [
        'name' => 'مكتب الرياض',
        'phone' => '966552491110',
        'phone_display' => '055 249 1110',
        'address' => 'طريق الملك فهد، برج الفيصلية، الرياض 12271',
        'map' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('شركة إتزان للمحاماة والاستشارات القانونية برج الفيصلية الرياض'),
    ];
}
