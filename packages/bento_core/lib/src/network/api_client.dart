import 'package:dio/dio.dart';

import 'api_exception.dart';

/// Thin wrapper around Dio that:
///  * sends `Accept-Language` (current UI locale) and the bearer token,
///  * unwraps the `{ success, data, meta }` envelope,
///  * converts every failure into an [ApiException] with a translatable code,
///  * retries idempotent GETs once on flaky connections.
class ApiClient {
  ApiClient({
    required String baseUrl,
    required this.localeTag,
    required this.token,
    this.onUnauthorized,
    Dio? dio,
  }) : _dio = dio ?? Dio() {
    _dio.options
      ..baseUrl = baseUrl
      ..connectTimeout = const Duration(seconds: 15)
      ..receiveTimeout = const Duration(seconds: 25)
      ..headers['Accept'] = 'application/json';
  }

  final Dio _dio;
  final String Function() localeTag;
  final String? Function() token;
  final void Function()? onUnauthorized;

  Future<T> get<T>(String path, {Map<String, dynamic>? query}) => _send<T>(
    () => _dio.get<Object?>(
      path,
      queryParameters: _clean(query),
      options: _options(),
    ),
    retry: true,
  );

  Future<T> post<T>(String path, {Object? body}) =>
      _send<T>(() => _dio.post<Object?>(path, data: body, options: _options()));

  Future<T> put<T>(String path, {Object? body}) =>
      _send<T>(() => _dio.put<Object?>(path, data: body, options: _options()));

  Future<T> delete<T>(String path) =>
      _send<T>(() => _dio.delete<Object?>(path, options: _options()));

  Options _options() {
    final bearer = token();
    return Options(
      headers: {
        'Accept-Language': localeTag(),
        if (bearer != null) 'Authorization': 'Bearer $bearer',
      },
    );
  }

  Future<T> _send<T>(
    Future<Response<Object?>> Function() request, {
    bool retry = false,
  }) async {
    try {
      final response = await request();
      return _unwrap<T>(response.data);
    } on DioException catch (e) {
      final transient =
          e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout;
      if (retry && transient) {
        return _send<T>(request);
      }
      throw _toApiException(e);
    }
  }

  T _unwrap<T>(Object? body) {
    if (body is Map<String, dynamic> && body['success'] == true) {
      return body['data'] as T;
    }
    throw const ApiException(ApiException.unknown);
  }

  ApiException _toApiException(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionError:
        return const ApiException(ApiException.network);
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return const ApiException(ApiException.timeout);
      default:
        break;
    }

    final status = e.response?.statusCode;
    final body = e.response?.data;
    if (status == 401) onUnauthorized?.call();

    if (body is Map<String, dynamic> && body['error'] is Map<String, dynamic>) {
      final error = body['error'] as Map<String, dynamic>;
      final fields = (error['fields'] as Map<String, dynamic>?)?.map(
        (key, value) =>
            MapEntry(key, (value as List).map((v) => v.toString()).toList()),
      );
      return ApiException(
        error['code'] as String? ?? ApiException.unknown,
        fields: fields,
        message: error['message'] as String?,
        statusCode: status,
      );
    }
    return ApiException(ApiException.unknown, statusCode: status);
  }

  static Map<String, dynamic>? _clean(Map<String, dynamic>? query) =>
      query == null ? null : (Map.of(query)..removeWhere((_, v) => v == null));
}
