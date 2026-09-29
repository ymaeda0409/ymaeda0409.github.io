import 'package:bento_core/bento_core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';

class Payment {
  const Payment({
    required this.id,
    required this.status,
    required this.method,
    required this.amount,
    required this.currency,
    this.phone,
    this.failureCode,
  });

  factory Payment.fromJson(Map<String, dynamic> json) => Payment(
    id: json['id'] as int,
    status: json['status'] as String,
    method: json['method'] as String,
    amount: json['amount'] as int,
    currency: json['currency'] as String,
    phone: json['phone'] as String?,
    failureCode: json['failure_code'] as String?,
  );

  final int id;

  /// PENDING → the customer must approve the prompt on their phone; PAID / FAILED are final.
  final String status;
  final String method;
  final int amount;
  final String currency;
  final String? phone;
  final String? failureCode;

  bool get isPending => status == 'PENDING';
  bool get isPaid => status == 'PAID';
}

final paymentRepositoryProvider = Provider<PaymentRepository>(
  (ref) => ApiPaymentRepository(ref.watch(apiClientProvider)),
);

abstract class PaymentRepository {
  /// Starts (or resumes) the mobile money charge for an order.
  Future<Payment> start(int orderId, String? phone);

  /// Current status; the server re-checks with the provider while pending.
  Future<Payment> status(int paymentId);
}

class ApiPaymentRepository implements PaymentRepository {
  ApiPaymentRepository(this._api);

  final ApiClient _api;

  @override
  Future<Payment> start(int orderId, String? phone) async => Payment.fromJson(
    await _api.post<Map<String, dynamic>>(
      '/payments',
      body: {'order_id': orderId, 'phone': ?phone},
    ),
  );

  @override
  Future<Payment> status(int paymentId) async => Payment.fromJson(
    await _api.get<Map<String, dynamic>>('/payments/$paymentId'),
  );
}
