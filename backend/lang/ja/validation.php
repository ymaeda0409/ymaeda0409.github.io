<?php

// Frequently used rules only; missing keys fall back to lang/en/validation.php.
return [
    'accepted' => ':attributeを承認してください。',
    'alpha_dash' => ':attributeには英数字・ハイフン・アンダースコアのみ使用できます。',
    'array' => ':attributeは配列で指定してください。',
    'between' => [
        'array' => ':attributeは:min〜:max個で指定してください。',
        'numeric' => ':attributeは:min〜:maxの間で指定してください。',
        'string' => ':attributeは:min〜:max文字で入力してください。',
    ],
    'boolean' => ':attributeはtrueまたはfalseで指定してください。',
    'date_format' => ':attributeは:format形式で入力してください。',
    'email' => ':attributeには有効なメールアドレスを入力してください。',
    'exists' => '選択された:attributeは正しくありません。',
    'gt' => [
        'numeric' => ':attributeは:valueより大きい値を指定してください。',
    ],
    'gte' => [
        'numeric' => ':attributeは:value以上を指定してください。',
    ],
    'in' => '選択された:attributeは正しくありません。',
    'integer' => ':attributeは整数で指定してください。',
    'lte' => [
        'numeric' => ':attributeは:value以下を指定してください。',
    ],
    'max' => [
        'array' => ':attributeは:max個以下で指定してください。',
        'numeric' => ':attributeは:max以下を指定してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'min' => [
        'array' => ':attributeは:min個以上で指定してください。',
        'numeric' => ':attributeは:min以上を指定してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'numeric' => ':attributeは数値で指定してください。',
    'prohibited' => ':attributeは指定できません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeは必須です。',
    'required_with' => ':valuesを指定する場合、:attributeは必須です。',
    'size' => [
        'array' => ':attributeは:size個で指定してください。',
        'string' => ':attributeは:size文字で入力してください。',
    ],
    'string' => ':attributeは文字列で指定してください。',
    'supported_locale' => '言語「:locale」には対応していません。',
    'timezone' => ':attributeには有効なタイムゾーンを指定してください。',
    'unique' => 'この:attributeは既に使用されています。',
    'url' => ':attributeには有効なURLを入力してください。',

    'attributes' => [
        'phone' => '電話番号',
        'code' => '認証コード',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
        'language' => '言語',
        'latitude' => '緯度',
        'longitude' => '経度',
        'store_id' => '店舗',
        'category_id' => 'カテゴリー',
        'price' => '価格',
    ],
];
