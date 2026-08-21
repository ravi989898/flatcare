import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

/// Thin wrapper around Dio: attaches the bearer token to every request,
/// unwraps the backend's {success, message, data} envelope, and converts
/// failures into ApiException so the rest of the app never touches Dio
/// types directly.
class ApiClient {
  ApiClient(this._tokenStorage, {this.onUnauthorized}) {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.apiBaseUrl,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        headers: {'Accept': 'application/json'},
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          if (error.response?.statusCode == 401) {
            onUnauthorized?.call();
          }
          handler.next(error);
        },
      ),
    );
  }

  final TokenStorage _tokenStorage;

  /// Called whenever any request comes back 401 — the AuthController wires
  /// this to clear local session state so go_router's redirect can kick the
  /// user back to the login screen.
  final void Function()? onUnauthorized;

  late final Dio _dio;

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) async {
    return _unwrap(() => _dio.get(path, queryParameters: query));
  }

  Future<Map<String, dynamic>> post(String path, {Map<String, dynamic>? data}) async {
    return _unwrap(() => _dio.post(path, data: data));
  }

  Future<Map<String, dynamic>> put(String path, {Map<String, dynamic>? data}) async {
    return _unwrap(() => _dio.put(path, data: data));
  }

  Future<Map<String, dynamic>> delete(String path) async {
    return _unwrap(() => _dio.delete(path));
  }

  Future<Map<String, dynamic>> _unwrap(Future<Response> Function() request) async {
    try {
      final response = await request();
      return Map<String, dynamic>.from(response.data as Map);
    } on DioException catch (error) {
      throw _toApiException(error);
    }
  }

  ApiException _toApiException(DioException error) {
    final data = error.response?.data;
    final statusCode = error.response?.statusCode;

    if (data is Map) {
      final message = data['message'] as String? ?? 'Something went wrong. Please try again.';
      final rawErrors = data['errors'];
      Map<String, List<String>>? fieldErrors;

      if (rawErrors is Map) {
        fieldErrors = rawErrors.map(
          (key, value) => MapEntry(key.toString(), List<String>.from(value as List)),
        );
      }

      return ApiException(message: message, statusCode: statusCode, fieldErrors: fieldErrors);
    }

    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.connectionError) {
      return ApiException(message: "Can't reach the server. Check your connection and try again.");
    }

    return ApiException(message: 'Something went wrong. Please try again.', statusCode: statusCode);
  }
}
