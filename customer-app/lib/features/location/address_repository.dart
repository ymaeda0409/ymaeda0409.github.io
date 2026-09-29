import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/providers.dart';
import 'address.dart';

final addressRepositoryProvider = Provider<AddressRepository>((ref) => ApiAddressRepository(ref.watch(apiClientProvider)));

abstract class AddressRepository {
  Future<List<Address>> list();
  Future<Address> create(Map<String, dynamic> data);
  Future<Address> update(int id, Map<String, dynamic> data);
  Future<void> delete(int id);
}

class ApiAddressRepository implements AddressRepository {
  ApiAddressRepository(this._api);

  final ApiClient _api;

  @override
  Future<List<Address>> list() async =>
      (await _api.get<List<dynamic>>('/addresses')).map((e) => Address.fromJson(e as Map<String, dynamic>)).toList();

  @override
  Future<Address> create(Map<String, dynamic> data) async =>
      Address.fromJson(await _api.post<Map<String, dynamic>>('/addresses', body: data));

  @override
  Future<Address> update(int id, Map<String, dynamic> data) async =>
      Address.fromJson(await _api.put<Map<String, dynamic>>('/addresses/$id', body: data));

  @override
  Future<void> delete(int id) => _api.delete<Object?>('/addresses/$id');
}

/// Saved addresses of the signed-in customer (empty when signed out).
final addressesProvider = FutureProvider<List<Address>>((ref) async {
  if (!ref.watch(sessionProvider.select((s) => s.isSignedIn))) return const [];
  return ref.watch(addressRepositoryProvider).list();
});
