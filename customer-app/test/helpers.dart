import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_customer/app.dart';
import 'package:malawi_bento_customer/core/providers.dart';
import 'package:malawi_bento_customer/core/storage/token_store.dart';
import 'package:malawi_bento_customer/features/catalog/catalog_repository.dart';
import 'package:malawi_bento_customer/features/catalog/models.dart';
import 'package:malawi_bento_customer/features/language/language_repository.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Localized fixtures, as the API would return them for each Accept-Language.
const productNames = {
  'en': ['Chicken Bento', 'Grilled chicken with rice and seasonal vegetables.'],
  'ny': ['Bento ya Nkhuku', 'Nkhuku yowotcha ndi mpunga komanso ndiwo zamasamba.'],
  'ja': ['チキン弁当', 'グリルチキンとご飯、季節の野菜の弁当です。'],
};

class FakeCatalogRepository implements CatalogRepository {
  FakeCatalogRepository(this.language);

  final String Function() language;

  StoreInfo get store => const StoreInfo(id: 1, name: 'Lilongwe Central Store', currency: 'MWK', isOpen: true);

  Product productFor(String lang) => Product(
        id: 1,
        categoryId: 1,
        name: productNames[lang]![0],
        description: productNames[lang]![1],
        price: 350000,
        currency: 'MWK',
        preparationMinutes: 15,
        isFeatured: true,
        isSoldOut: false,
        optionGroups: [
          OptionGroup(id: 1, name: 'rice-$lang', minSelect: 1, maxSelect: 1, options: const [
            ProductOption(id: 1, name: 'regular', price: 0),
            ProductOption(id: 2, name: 'large', price: 50000),
          ]),
        ],
      );

  @override
  Future<List<AvailableStore>> availableStores(double latitude, double longitude) async => [
        AvailableStore(
          store: store,
          kitchenId: 1,
          deliveryZoneId: 1,
          distanceKm: 1.2,
          deliveryFee: 150000,
          currency: 'MWK',
          isOpen: true,
        ),
      ];

  @override
  Future<List<Category>> categories(int storeId) async =>
      [Category(id: 1, code: 'bento', name: {'en': 'Bento', 'ny': 'Bento', 'ja': '弁当'}[language()]!)];

  @override
  Future<List<Product>> products(int storeId, {int? categoryId, bool featured = false}) async =>
      [productFor(language())];

  @override
  Future<Product> product(int storeId, int productId) async => productFor(language());
}

Future<ProviderContainer> pumpApp(
  WidgetTester tester, {
  Map<String, Object> prefs = const {},
  String? token,
  List overrides = const [],
  Size size = const Size(360, 740),
  double textScale = 1.0,
}) async {
  SharedPreferences.setMockInitialValues(prefs);
  final sharedPrefs = await SharedPreferences.getInstance();
  tester.view.physicalSize = size * tester.view.devicePixelRatio;
  tester.platformDispatcher.textScaleFactorTestValue = textScale;
  addTearDown(tester.view.reset);
  addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);

  final container = ProviderContainer(
    retry: (_, _) => null,
    overrides: [
      sharedPreferencesProvider.overrideWithValue(sharedPrefs),
      tokenStoreProvider.overrideWithValue(MemoryTokenStore(token)),
      initialTokenProvider.overrideWithValue(token),
      availableLocalesProvider.overrideWith((ref) async => const [Locale('en'), Locale('ny'), Locale('ja')]),
      catalogRepositoryProvider.overrideWith(
        (ref) => FakeCatalogRepository(() => ref.read(effectiveLocaleProvider).languageCode),
      ),
      ...overrides,
    ],
  );
  addTearDown(container.dispose);

  await tester.pumpWidget(UncontrolledProviderScope(container: container, child: const BentoApp()));
  await tester.pumpAndSettle();
  return container;
}
