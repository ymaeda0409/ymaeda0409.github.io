<?php

// Chichewa — initial translation, requires native-speaker review before release.
// Frequently used rules only; missing keys fall back to lang/en/validation.php.
return [
    'array' => ':attribute iyenera kukhala mndandanda.',
    'between' => [
        'numeric' => ':attribute iyenera kukhala pakati pa :min ndi :max.',
        'string' => ':attribute iyenera kukhala ndi zilembo pakati pa :min ndi :max.',
    ],
    'boolean' => ':attribute iyenera kukhala inde kapena ayi.',
    'email' => ':attribute iyenera kukhala imelo yovomerezeka.',
    'exists' => ':attribute yosankhidwa si yolondola.',
    'in' => ':attribute yosankhidwa si yolondola.',
    'integer' => ':attribute iyenera kukhala nambala yathunthu.',
    'max' => [
        'numeric' => ':attribute isapitirire :max.',
        'string' => ':attribute isapitirire zilembo :max.',
    ],
    'min' => [
        'numeric' => ':attribute ikhale osachepera :min.',
        'string' => ':attribute ikhale ndi zilembo zosachepera :min.',
    ],
    'numeric' => ':attribute iyenera kukhala nambala.',
    'regex' => 'Kalembedwe ka :attribute si kolondola.',
    'required' => ':attribute ikufunika.',
    'string' => ':attribute iyenera kukhala mawu.',
    'supported_locale' => 'Chilankhulo ":locale" sichikupezeka.',
    'unique' => ':attribute iyi yagwiritsidwa kale ntchito.',

    'attributes' => [
        'phone' => 'Nambala ya foni',
        'code' => 'Nambala yotsimikizira',
        'email' => 'Imelo',
        'password' => 'Mawu achinsinsi',
        'name' => 'Dzina',
        'language' => 'Chilankhulo',
    ],
];
