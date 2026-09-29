/// Error from the API envelope (`error.code`) or a transport problem.
/// UI code translates [code]; it never shows raw server text by default.
class ApiException implements Exception {
  const ApiException(this.code, {this.fields, this.message, this.statusCode});

  static const network = 'NETWORK';
  static const timeout = 'TIMEOUT';
  static const unknown = 'UNKNOWN';

  final String code;
  final Map<String, List<String>>? fields;

  /// Server-side localized helper message (fallback/debug only).
  final String? message;
  final int? statusCode;

  /// Server-localized message for a form field, if the API returned one.
  String? fieldMessage(String field) => fields?[field]?.firstOrNull;

  @override
  String toString() => 'ApiException($code, $statusCode)';
}
