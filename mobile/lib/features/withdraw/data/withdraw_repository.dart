import '../../../core/network/api_result.dart';
import '../../offerwall/models/page_result.dart';
import '../models/cancel_result.dart';
import '../models/conversion_info.dart';
import '../models/withdraw_detail.dart';
import '../models/withdraw_method.dart';
import '../models/withdraw_request.dart';

/// Contract for the withdraw feature: methods, live conversion, request
/// creation, paginated history, detail + timeline, and cancellation.
abstract interface class WithdrawRepository {
  Future<ApiResult<List<WithdrawMethod>>> fetchMethods();

  Future<ApiResult<ConversionInfo>> fetchConversion();

  Future<ApiResult<WithdrawRequest>> createRequest({
    required String methodCode,
    required int coinsAmount,
    required Map<String, dynamic> paymentDetail,
  });

  Future<ApiResult<PageResult<WithdrawRequest>>> fetchHistory({
    String? status,
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<WithdrawDetail>> fetchDetail(String uuid);

  Future<ApiResult<CancelResult>> cancel(String uuid);
}
