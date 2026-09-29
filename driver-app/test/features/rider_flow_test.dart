import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_driver/core/providers.dart';
import 'package:malawi_bento_driver/features/delivery/driver_controller.dart';
import 'package:malawi_bento_driver/features/delivery/models.dart';
import 'package:malawi_bento_driver/l10n/generated/app_localizations.dart';

import '../helpers.dart';

void main() {
  testWidgets('first launch asks for the language, then sign-in', (
    tester,
  ) async {
    tester.platformDispatcher.localesTestValue = const [Locale('ny', 'MW')];
    addTearDown(tester.platformDispatcher.clearLocalesTestValue);

    final container = await pumpRider(
      tester,
      repo: FakeDriverRepository(),
      prefs: const {},
      token: null,
    );

    expect(find.text('Sankhani chilankhulo chanu'), findsOneWidget);
    await tester.tap(find.text('日本語'));
    await tester.pumpAndSettle();
    expect(
      find.text('言語を選択してください'),
      findsOneWidget,
      reason: 'preview switches instantly',
    );

    await tester.tap(find.text('続ける'));
    await tester.pumpAndSettle();
    expect(container.read(localeProvider), const Locale('ja'));
    expect(find.text('ライダーログイン'), findsOneWidget);
    await unmount(tester);
  });

  testWidgets(
    'full delivery: online → accept → pickup → arrive → PIN → complete',
    (tester) async {
      final repo = FakeDriverRepository();
      final launched = <Uri>[];
      await pumpRider(tester, repo: repo, launched: launched);

      expect(find.text('You are offline'), findsOneWidget);
      await tester.tap(find.text('GO ONLINE'));
      await tester.pumpAndSettle();

      expect(find.text('New delivery'), findsOneWidget);
      expect(find.text('Collect MK 9,500 in cash'), findsOneWidget);
      await tester.tap(find.text('ACCEPT'));
      await tester.pumpAndSettle();

      expect(find.text('Pick up at'), findsOneWidget);
      await tester.tap(find.text('Navigate'));
      expect(
        launched.single.toString(),
        contains('destination=-13.9626%2C33.7741'),
      );
      await tester.scrollUntilVisible(find.text('I HAVE THE ORDER'), 200);
      await tester.tap(find.text('I HAVE THE ORDER'));
      await tester.pumpAndSettle();

      expect(find.text('Near the blue gate'), findsOneWidget);
      await tester.scrollUntilVisible(find.text('I HAVE ARRIVED'), 200);
      await tester.tap(find.text('I HAVE ARRIVED'));
      await tester.pumpAndSettle();

      for (final d in ['9', '9', '9', '9']) {
        await tester.tap(find.widgetWithText(FilledButton, d).first);
      }
      await tester.pump();
      await tester.scrollUntilVisible(find.text('CONFIRM DELIVERY'), 200);
      await tester.tap(find.text('CONFIRM DELIVERY'));
      await tester.pumpAndSettle();
      expect(
        find.text('The PIN is incorrect. Ask the customer again.'),
        findsOneWidget,
      );

      await tester.scrollUntilVisible(
        find.widgetWithText(FilledButton, '1'),
        -200,
      );
      for (final d in ['1', '2', '3', '4']) {
        await tester.tap(find.widgetWithText(FilledButton, d).first);
      }
      await tester.pump();
      await tester.scrollUntilVisible(find.text('CONFIRM DELIVERY'), 200);
      await tester.tap(find.text('CONFIRM DELIVERY'));
      await tester.pumpAndSettle();

      expect(find.text('Delivered!'), findsOneWidget);
      expect(repo.calls, [
        'accept',
        'pickup',
        'arrive',
        'complete',
        'complete',
      ]);
      await unmount(tester);
    },
  );

  testWidgets(
    'pickup without signal is queued, shown as pending and replayed',
    (tester) async {
      final repo = FakeDriverRepository();
      final container = await pumpRider(tester, repo: repo);
      await tester.tap(find.text('GO ONLINE'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('ACCEPT'));
      await tester.pumpAndSettle();

      repo.offlineNetwork = true;
      await tester.scrollUntilVisible(find.text('I HAVE THE ORDER'), 200);
      await tester.tap(find.text('I HAVE THE ORDER'));
      await tester.pumpAndSettle();

      expect(
        find.text('I HAVE ARRIVED'),
        findsOneWidget,
        reason: 'rider can continue offline',
      );
      expect(
        find.text('Offline — your update will be sent automatically.'),
        findsOneWidget,
      );
      expect(repo.calls, ['accept']);

      repo.offlineNetwork = false;
      await container.read(driverControllerProvider.notifier).tick();
      await tester.pumpAndSettle();
      expect(repo.calls, ['accept', 'pickup']);
      expect(
        find.text('Offline — your update will be sent automatically.'),
        findsNothing,
      );
      await unmount(tester);
    },
  );

  testWidgets(
    'current delivery is restored from the phone after a restart without signal',
    (tester) async {
      final repo = FakeDriverRepository()..offlineNetwork = true;
      final firstRun = FakeDriverRepository();
      final container = await pumpRider(tester, repo: firstRun);
      await tester.tap(find.text('GO ONLINE'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('ACCEPT'));
      await tester.pumpAndSettle();
      final saved = container
          .read(sharedPreferencesProvider)
          .getString('active_delivery')!;
      await unmount(tester);

      await pumpRider(
        tester,
        repo: repo,
        prefs: {'locale': 'en', 'active_delivery': saved},
      );
      expect(find.text('Pick up at'), findsOneWidget);
      await unmount(tester);
    },
  );

  for (final lang in ['en', 'ny', 'ja']) {
    testWidgets(
      'online, request, pickup and PIN screens fit a small phone with large text ($lang)',
      (tester) async {
        final l = lookupAppLocalizations(Locale(lang));
        final repo = FakeDriverRepository();
        final container = await pumpRider(
          tester,
          repo: repo,
          prefs: {'locale': lang},
          size: const Size(320, 640),
          textScale: 1.3,
        );
        expect(tester.takeException(), isNull);

        await tester.tap(find.text(l.driver_go_online));
        await tester.pumpAndSettle();
        expect(find.text(l.driver_new_delivery), findsOneWidget);
        expect(tester.takeException(), isNull);

        await tester.ensureVisible(find.text(l.driver_accept));
        await tester.pumpAndSettle();
        await tester.tap(find.text(l.driver_accept));
        await tester.pumpAndSettle();
        expect(find.text(l.driver_pickup_at), findsOneWidget);
        expect(tester.takeException(), isNull);

        repo.active = Delivery.fromJson(deliveryJson(status: 'ARRIVED'));
        await container.read(driverControllerProvider.notifier).refresh();
        await tester.pumpAndSettle();
        expect(find.text(l.driver_pin_title), findsOneWidget);
        expect(tester.takeException(), isNull);
        await unmount(tester);
      },
    );
  }
}
