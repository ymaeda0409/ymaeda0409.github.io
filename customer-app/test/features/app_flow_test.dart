import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_customer/core/providers.dart';
import 'package:malawi_bento_customer/features/cart/cart.dart';

import '../helpers.dart';

const _located = {
  'delivery_location':
      '{"latitude":-13.97,"longitude":33.78,"label":"Home","address_id":null}',
};

void main() {
  testWidgets(
    'first launch preselects the device language and switches instantly',
    (tester) async {
      tester.platformDispatcher.localesTestValue = const [Locale('ja', 'JP')];
      addTearDown(tester.platformDispatcher.clearLocalesTestValue);

      final container = await pumpApp(tester);

      expect(find.text('言語を選択してください'), findsOneWidget);

      await tester.tap(find.text('Chichewa'));
      await tester.pumpAndSettle();
      expect(
        find.text('Sankhani chilankhulo chanu'),
        findsOneWidget,
        reason: 'UI switches without restart',
      );

      await tester.tap(find.text('Pitirizani'));
      await tester.pumpAndSettle();
      expect(container.read(localeProvider), const Locale('ny'));
      expect(
        container.read(sharedPreferencesProvider).getString('locale'),
        'ny',
      );
    },
  );

  testWidgets('unsupported device language defaults to English', (
    tester,
  ) async {
    tester.platformDispatcher.localesTestValue = const [Locale('fr', 'FR')];
    addTearDown(tester.platformDispatcher.clearLocalesTestValue);

    await pumpApp(tester);

    expect(find.text('Choose your language'), findsOneWidget);
  });

  for (final lang in ['en', 'ny', 'ja']) {
    testWidgets(
      'home renders translated content in $lang on a small phone with large text',
      (tester) async {
        // 320 dp wide + 130 % text: overflow errors fail the test.
        await pumpApp(
          tester,
          prefs: {'locale': lang, ..._located},
          size: const Size(320, 640),
          textScale: 1.3,
        );

        expect(find.text(productNames[lang]![0]), findsWidgets);
        expect(tester.takeException(), isNull);
      },
    );
  }

  testWidgets('changing language in settings refetches product names', (
    tester,
  ) async {
    await pumpApp(tester, prefs: {'locale': 'en', ..._located});
    expect(find.text('Chicken Bento'), findsWidgets);

    await tester.tap(find.text('Account'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Language'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('日本語'));
    await tester.pumpAndSettle();
    await tester.tap(find.byType(BackButton));
    await tester.pumpAndSettle();
    await tester.tap(find.text('ホーム'));
    await tester.pumpAndSettle();

    expect(find.text('チキン弁当'), findsWidgets);
    expect(find.text('Chicken Bento'), findsNothing);
  });

  testWidgets('add to cart with the required option preselected', (
    tester,
  ) async {
    final container = await pumpApp(
      tester,
      prefs: {'locale': 'en', ..._located},
    );

    await tester.tap(find.text('Chicken Bento').first);
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('large'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('large'));
    await tester.pumpAndSettle();
    await tester.tap(find.byType(FilledButton));
    await tester.pumpAndSettle();

    final cart = container.read(cartProvider);
    expect(cart.lines.single.optionIds, [2]);
    expect(cart.subtotal, 400000);
  });

  testWidgets('checkout requires sign-in', (tester) async {
    final container = await pumpApp(
      tester,
      prefs: {'locale': 'ja', ..._located},
    );
    await container
        .read(cartProvider.notifier)
        .add(
          store: FakeCatalogRepository(() => 'ja').store,
          product: FakeCatalogRepository(() => 'ja').productFor('ja'),
          options: const [],
          quantity: 1,
        );
    await tester.pumpAndSettle();

    await tester.tap(find.text('カート'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('レジに進む'));
    await tester.pumpAndSettle();

    expect(find.text('電話番号でログイン'), findsOneWidget);
    expect(find.text('続行するにはログインしてください'), findsOneWidget);
  });
}
