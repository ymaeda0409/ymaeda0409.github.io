import 'package:bento_core/bento_core.dart';
import 'package:malawi_bento_customer/core/providers.dart';
import 'package:malawi_bento_customer/features/auth/user.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class RecordingRegistrar extends DeviceRegistrar {
  RecordingRegistrar(ApiClient api)
    : super(api, const NoPushTokenSource(), app: 'customer');

  int registered = 0;

  @override
  Future<bool> register() async {
    registered++;
    return true;
  }
}

void main() {
  test('signing in registers the device for push notifications', () async {
    SharedPreferences.setMockInitialValues({});
    final prefs = await SharedPreferences.getInstance();
    late RecordingRegistrar registrar;
    final container = ProviderContainer(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(prefs),
        tokenStoreProvider.overrideWithValue(MemoryTokenStore()),
        deviceRegistrarProvider.overrideWith(
          (ref) => registrar = RecordingRegistrar(ref.watch(apiClientProvider)),
        ),
      ],
    );
    addTearDown(container.dispose);

    await container
        .read(sessionProvider.notifier)
        .signIn(
          'token',
          const AppUser(id: 1, role: 'CUSTOMER', preferredLanguage: 'ny'),
        );

    expect(registrar.registered, 1);
    expect(container.read(sessionProvider).isSignedIn, isTrue);
  });

  test(
    'the default token source leaves the app working without Firebase',
    () async {
      expect(await const NoPushTokenSource().token(), isNull);
    },
  );
}
