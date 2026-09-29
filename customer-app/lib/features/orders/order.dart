/// Order as returned by the API. Status/payment values are codes; UI translates them.
class OrderLine {
  const OrderLine({
    required this.name,
    required this.quantity,
    required this.total,
    required this.options,
  });

  factory OrderLine.fromJson(Map<String, dynamic> json) => OrderLine(
    name: json['name'] as String,
    quantity: json['quantity'] as int,
    total: json['total'] as int,
    options: ((json['options'] as List?) ?? const [])
        .map((o) => (o as Map<String, dynamic>)['name'] as String)
        .toList(),
  );

  /// Snapshot in the language the order was placed in.
  final String name;
  final int quantity;
  final int total;
  final List<String> options;
}

class TimelineEntry {
  const TimelineEntry(this.status, this.at);

  final String status;
  final DateTime at;
}

class Order {
  const Order({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.currency,
    required this.subtotal,
    required this.deliveryFee,
    required this.serviceFee,
    required this.discount,
    required this.total,
    required this.orderedAt,
    this.deliveryPin,
    this.storeName,
    this.scheduledAt,
    this.items = const [],
    this.itemCount = 0,
    this.timeline = const [],
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    final items = ((json['items'] as List?) ?? const [])
        .map((i) => OrderLine.fromJson(i as Map<String, dynamic>))
        .toList();
    return Order(
      id: json['id'] as int,
      orderNumber: json['order_number'] as String,
      status: json['status'] as String,
      paymentMethod: json['payment_method'] as String,
      paymentStatus: json['payment_status'] as String,
      currency: json['currency'] as String,
      subtotal: json['subtotal'] as int,
      deliveryFee: json['delivery_fee'] as int,
      serviceFee: json['service_fee'] as int,
      discount: json['discount'] as int,
      total: json['total'] as int,
      orderedAt: DateTime.parse(json['ordered_at'] as String),
      deliveryPin: json['delivery_pin'] as String?,
      storeName: (json['store'] as Map<String, dynamic>?)?['name'] as String?,
      scheduledAt: json['scheduled_at'] == null
          ? null
          : DateTime.parse(json['scheduled_at'] as String),
      items: items,
      itemCount:
          json['item_count'] as int? ??
          items.fold<int>(0, (s, i) => s + i.quantity),
      timeline: ((json['timeline'] as List?) ?? const [])
          .map(
            (t) => TimelineEntry(
              (t as Map<String, dynamic>)['status'] as String,
              DateTime.parse(t['at'] as String),
            ),
          )
          .toList(),
    );
  }

  final int id;
  final String orderNumber;
  final String status;
  final String paymentMethod;
  final String paymentStatus;
  final String currency;
  final int subtotal;
  final int deliveryFee;
  final int serviceFee;
  final int discount;
  final int total;
  final DateTime orderedAt;
  final String? deliveryPin;
  final String? storeName;
  final DateTime? scheduledAt;
  final List<OrderLine> items;
  final int itemCount;
  final List<TimelineEntry> timeline;

  static const _finalStatuses = {'DELIVERED', 'CANCELLED', 'FAILED_DELIVERY'};
  static const _customerCancellable = {'NEW', 'CONFIRMED'};

  bool get isActive => !_finalStatuses.contains(status);
  bool get canCancel => _customerCancellable.contains(status);

  /// Mobile money order still waiting for (or after a failed) payment.
  bool get needsPayment =>
      paymentMethod != 'CASH' && status == 'NEW' && paymentStatus != 'PAID';
}

/// Server-calculated totals for the current cart.
class Quote {
  const Quote({
    required this.currency,
    required this.subtotal,
    required this.deliveryFee,
    required this.serviceFee,
    required this.discount,
    required this.total,
  });

  factory Quote.fromJson(Map<String, dynamic> json) => Quote(
    currency: json['currency'] as String,
    subtotal: json['subtotal'] as int,
    deliveryFee: json['delivery_fee'] as int,
    serviceFee: json['service_fee'] as int,
    discount: json['discount'] as int,
    total: json['total'] as int,
  );

  final String currency;
  final int subtotal;
  final int deliveryFee;
  final int serviceFee;
  final int discount;
  final int total;
}
