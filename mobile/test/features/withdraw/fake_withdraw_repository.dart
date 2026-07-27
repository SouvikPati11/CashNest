import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/offerwall/models/page_result.dart';
import 'package:cashnest/features/withdraw/data/withdraw_repository.dart';
import 'package:cashnest/features/withdraw/models/cancel_result.dart';
import 'package:cashnest/features/withdraw/models/conversion_info.dart';
import 'package:cashnest/features/withdraw/models/withdraw_detail.dart';
import 'package:cashnest/features/withdraw/models/withdraw_method.dart';
import 'package:cashnest/features/withdraw/models/withdraw_request.dart';

/// A configurable fake [WithdrawRepository] for controller tests.
class FakeWithdrawRepository implements WithdrawRepository {
  ApiResult<List<WithdrawMethod>> methods = const ApiResult.success([
    WithdrawMethod(
      code: 'upi',
      name: 'UPI',
      minCoins: 5000,
      maxCoins: 100000,
      feePercent: '0.0200',
      detailSchema: {'upi_id': 'string'},
    ),
  ]);
  ApiResult<ConversionInfo> conversion = const ApiResult.success(ConversionInfo(
    coinToCashRate: '0.00100000',
    currency: 'INR',
    minWithdrawCoins: 5000,
    maxWithdrawCoins: 100000,
  ));
  ApiResult<WithdrawRequest> createResult = const ApiResult.success(WithdrawRequest(
    uuid: 'wr_1',
    status: 'pending',
    coinsAmount: 5000,
    cashAmount: '5.0000',
    netAmount: '4.9000',
    feeAmount: '0.1000',
  ));

  String? lastStatus;
  String? lastMethodCode;
  int? lastCoins;
  Map<String, dynamic>? lastDetail;

  @override
  Future<ApiResult<List<WithdrawMethod>>> fetchMethods() async => methods;

  @override
  Future<ApiResult<ConversionInfo>> fetchConversion() async => conversion;

  @override
  Future<ApiResult<WithdrawRequest>> createRequest({
    required String methodCode,
    required int coinsAmount,
    required Map<String, dynamic> paymentDetail,
  }) async {
    lastMethodCode = methodCode;
    lastCoins = coinsAmount;
    lastDetail = paymentDetail;
    return createResult;
  }

  @override
  Future<ApiResult<PageResult<WithdrawRequest>>> fetchHistory({
    String? status,
    String? cursor,
    int limit = 20,
  }) async {
    lastStatus = status;
    return const ApiResult.success(PageResult(items: [
      WithdrawRequest(uuid: 'wr_1', status: 'pending', coinsAmount: 5000, cashAmount: '5', netAmount: '4.9'),
      WithdrawRequest(uuid: 'wr_2', status: 'paid', coinsAmount: 8000, cashAmount: '8', netAmount: '7.8'),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<WithdrawDetail>> fetchDetail(String uuid) async =>
      const ApiResult.success(WithdrawDetail(
        request: WithdrawRequest(uuid: 'wr_1', status: 'pending', coinsAmount: 5000, cashAmount: '5', netAmount: '4.9'),
      ));

  @override
  Future<ApiResult<CancelResult>> cancel(String uuid) async =>
      const ApiResult.success(CancelResult(status: 'cancelled', refundedCoins: 5000, newBalance: 9000));
}
