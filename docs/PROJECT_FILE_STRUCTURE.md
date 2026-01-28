app/
├── Application/
│   ├── Admin/
│   │   └── AdminProfileUpdateService.php
│   ├── Contracts/
│   │   ├── AuditTrailRecorderInterface.php
│   │   ├── AuthoritativeAuditRecorderInterface.php
│   │   ├── BehaviorTraceRecorderInterface.php
│   │   ├── DeliveryOperationsRecorderInterface.php
│   │   ├── DiagnosticsTelemetryRecorderInterface.php
│   │   └── SecuritySignalsRecorderInterface.php
│   ├── Crypto/
│   │   ├── AdminIdentifierCryptoServiceInterface.php
│   │   ├── DTO/
│   │   │   └── PasswordHashDTO.php
│   │   ├── NotificationCryptoServiceInterface.php
│   │   ├── PasswordCryptoServiceInterface.php
│   │   └── TotpSecretCryptoServiceInterface.php
│   ├── Services/
│   │   ├── AuditTrailService.php
│   │   ├── AuthoritativeAuditService.php
│   │   ├── BehaviorTraceService.php
│   │   ├── DeliveryOperationsService.php
│   │   ├── DiagnosticsTelemetryService.php
│   │   └── SecuritySignalsService.php
│   └── Verification/
│       ├── Enum/
│       │   ├── EmailTemplateEnum.php
│       │   └── NotificationSenderTypeEnum.php
│       ├── VerificationNotificationDispatcher.php
│       └── VerificationNotificationDispatcherInterface.php
├── Bootstrap/
│   ├── Container.php
│   └── http.php
├── Context/
│   ├── ActorContext.php
│   ├── AdminContext.php
│   └── RequestContext.php
├── Domain/
│   ├── Actor/
│   │   ├── Actor.php
│   │   └── ActorType.php
│   ├── Admin/
│   │   ├── DTO/
│   │   │   └── AdminEmailListItemDTO.php
│   │   ├── Enum/
│   │   │   ├── AdminActivityActionEnum.php
│   │   │   └── AdminStatusEnum.php
│   │   └── Reader/
│   │       ├── AdminBasicInfoReaderInterface.php
│   │       ├── AdminEmailReaderInterface.php
│   │       ├── AdminProfileReaderInterface.php
│   │       └── AdminQueryReaderInterface.php
│   ├── Contracts/
│   │   ├── ActorProviderInterface.php
│   │   ├── AdminActionMetadataInterface.php
│   │   ├── AdminActivityQueryInterface.php
│   │   ├── AdminDirectPermissionRepositoryInterface.php
│   │   ├── AdminEmailVerificationRepositoryInterface.php
│   │   ├── AdminIdentifierLookupInterface.php
│   │   ├── AdminNotificationChannelRepositoryInterface.php
│   │   ├── AdminNotificationHistoryReaderInterface.php
│   │   ├── AdminNotificationPersistenceWriterInterface.php
│   │   ├── AdminNotificationPreferenceReaderInterface.php
│   │   ├── AdminNotificationPreferenceRepositoryInterface.php
│   │   ├── AdminNotificationPreferenceWriterInterface.php
│   │   ├── AdminNotificationReadMarkerInterface.php
│   │   ├── AdminPasswordRepositoryInterface.php
│   │   ├── AdminRoleRepositoryInterface.php
│   │   ├── AdminSecurityEventReaderInterface.php
│   │   ├── AdminSelfAuditReaderInterface.php
│   │   ├── AdminSessionRepositoryInterface.php
│   │   ├── AdminSessionValidationRepositoryInterface.php
│   │   ├── AdminTargetedAuditReaderInterface.php
│   │   ├── AdminTotpSecretRepositoryInterface.php
│   │   ├── AdminTotpSecretStoreInterface.php
│   │   ├── AuthoritativeSecurityAuditWriterInterface.php
│   │   ├── FailedNotificationRepositoryInterface.php
│   │   ├── NotificationChannelPreferenceResolverInterface.php
│   │   ├── NotificationReadRepositoryInterface.php
│   │   ├── NotificationRoutingInterface.php
│   │   ├── PermissionMapperInterface.php
│   │   ├── PermissionsMetadataRepositoryInterface.php
│   │   ├── PermissionsReaderRepositoryInterface.php
│   │   ├── RememberMeRepositoryInterface.php
│   │   ├── RolePermissionRepositoryInterface.php
│   │   ├── Roles/
│   │   │   ├── RoleCreateRepositoryInterface.php
│   │   │   ├── RoleRenameRepositoryInterface.php
│   │   │   ├── RoleRepositoryInterface.php
│   │   │   ├── RoleToggleRepositoryInterface.php
│   │   │   ├── RolesMetadataRepositoryInterface.php
│   │   │   └── RolesReaderRepositoryInterface.php
│   │   ├── StepUpGrantRepositoryInterface.php
│   │   ├── TelemetryAuditLoggerInterface.php
│   │   ├── TotpServiceInterface.php
│   │   ├── Ui/
│   │   │   └── NavigationProviderInterface.php
│   │   ├── VerificationCodeGeneratorInterface.php
│   │   ├── VerificationCodePolicyResolverInterface.php
│   │   ├── VerificationCodeRepositoryInterface.php
│   │   └── VerificationCodeValidatorInterface.php
│   ├── DTO/
│   │   ├── ActivityLog/
│   │   │   ├── ActivityLogListItemDTO.php
│   │   │   └── ActivityLogListResponseDTO.php
│   │   ├── AdminActionDescriptorDTO.php
│   │   ├── AdminActivityDTO.php
│   │   ├── AdminAlertDTO.php
│   │   ├── AdminConfigDTO.php
│   │   ├── AdminEmailIdentifierDTO.php
│   │   ├── AdminList/
│   │   │   ├── AdminListItemDTO.php
│   │   │   ├── AdminListQueryDTO.php
│   │   │   └── AdminListResponseDTO.php
│   │   ├── AdminLoginResultDTO.php
│   │   ├── AdminNotificationChannelDTO.php
│   │   ├── AdminNotificationDTO.php
│   │   ├── AdminNotificationPreferenceDTO.php
│   │   ├── AdminPasswordRecordDTO.php
│   │   ├── Audit/
│   │   │   ├── ActorAuditLogViewDTO.php
│   │   │   ├── GetActionsTargetingMeQueryDTO.php
│   │   │   ├── GetMyActionsQueryDTO.php
│   │   │   ├── GetMySecurityEventsQueryDTO.php
│   │   │   ├── SecurityEventViewDTO.php
│   │   │   └── TargetAuditLogViewDTO.php
│   │   ├── AuditEventDTO.php
│   │   ├── Common/
│   │   │   └── PaginationDTO.php
│   │   ├── Crypto/
│   │   │   └── EncryptedPayloadDTO.php
│   │   ├── Email/
│   │   │   ├── EmailPayloadInterface.php
│   │   │   ├── EmailVerificationPayloadDTO.php
│   │   │   └── OtpEmailPayloadDTO.php
│   │   ├── FailedNotificationDTO.php
│   │   ├── GeneratedVerificationCode.php
│   │   ├── LegacyAuditEventDTO.php
│   │   ├── LoginRequestDTO.php
│   │   ├── LoginResponseDTO.php
│   │   ├── Notification/
│   │   │   ├── AckNotificationReadDTO.php
│   │   │   ├── ChannelResolutionMetadataDTO.php
│   │   │   ├── ChannelResolutionResultDTO.php
│   │   │   ├── DeliveryResultDTO.php
│   │   │   ├── History/
│   │   │   │   ├── AdminNotificationHistoryQueryDTO.php
│   │   │   │   ├── AdminNotificationHistoryViewDTO.php
│   │   │   │   └── MarkNotificationReadDTO.php
│   │   │   ├── NotificationDeliveryDTO.php
│   │   │   ├── NotificationRoutingContextDTO.php
│   │   │   ├── PersistNotificationDTO.php
│   │   │   └── Preference/
│   │   │       ├── AdminNotificationPreferenceDTO.php
│   │   │       ├── AdminNotificationPreferenceListDTO.php
│   │   │       ├── GetAdminPreferencesByTypeQueryDTO.php
│   │   │       ├── GetAdminPreferencesQueryDTO.php
│   │   │       └── UpdateAdminNotificationPreferenceDTO.php
│   │   ├── NotificationMessageDTO.php
│   │   ├── NotificationSummaryDTO.php
│   │   ├── Permission/
│   │   │   ├── PermissionListItemDTO.php
│   │   │   └── PermissionsQueryResponseDTO.php
│   │   ├── RememberMeTokenDTO.php
│   │   ├── Request/
│   │   │   ├── CreateAdminEmailRequestDTO.php
│   │   │   └── VerifyAdminEmailRequestDTO.php
│   │   ├── Response/
│   │   │   ├── ActionResultResponseDTO.php
│   │   │   ├── AdminCreateResponseDTO.php
│   │   │   ├── AdminEmailResponseDTO.php
│   │   │   └── VerificationResponseDTO.php
│   │   ├── Roles/
│   │   │   ├── RolesListItemDTO.php
│   │   │   └── RolesQueryResponseDTO.php
│   │   ├── SecurityEventDTO.php
│   │   ├── Session/
│   │   │   ├── SessionListItemDTO.php
│   │   │   └── SessionListResponseDTO.php
│   │   ├── StepUpGrant.php
│   │   ├── TotpEnrollmentConfig.php
│   │   ├── TotpVerificationResultDTO.php
│   │   ├── TwoFactorEnrollmentViewDTO.php
│   │   ├── Ui/
│   │   │   ├── NavigationItemDTO.php
│   │   │   └── UiConfigDTO.php
│   │   ├── VerificationCode.php
│   │   ├── VerificationPolicy.php
│   │   └── VerificationResult.php
│   ├── Enum/
│   │   ├── ActionResult.php
│   │   ├── IdentifierType.php
│   │   ├── IdentityTypeEnum.php
│   │   ├── NotificationChannelType.php
│   │   ├── RecoveryTransitionReason.php
│   │   ├── RoleLevel.php
│   │   ├── Scope.php
│   │   ├── SessionState.php
│   │   ├── VerificationCodeStatus.php
│   │   ├── VerificationFailureReasonEnum.php
│   │   ├── VerificationPurposeEnum.php
│   │   └── VerificationStatus.php
│   ├── Exception/
│   │   ├── AuthStateException.php
│   │   ├── EntityAlreadyExistsException.php
│   │   ├── EntityNotFoundException.php
│   │   ├── ExpiredSessionException.php
│   │   ├── IdentifierNotFoundException.php
│   │   ├── InvalidCredentialsException.php
│   │   ├── InvalidIdentifierFormatException.php
│   │   ├── InvalidIdentifierStateException.php
│   │   ├── InvalidOperationException.php
│   │   ├── InvalidSessionException.php
│   │   ├── MustChangePasswordException.php
│   │   ├── PermissionDeniedException.php
│   │   ├── RecoveryLockException.php
│   │   ├── RevokedSessionException.php
│   │   ├── TwoFactorAlreadyEnrolledException.php
│   │   ├── TwoFactorEnrollmentFailedException.php
│   │   ├── UnauthorizedException.php
│   │   └── UnsupportedNotificationChannelException.php
│   ├── List/
│   │   ├── AdminListCapabilities.php
│   │   ├── ListCapabilities.php
│   │   ├── ListQueryDTO.php
│   │   ├── PermissionsCapabilities.php
│   │   └── RolesCapabilities.php
│   ├── Notification/
│   │   ├── NotificationChannelType.php
│   │   └── NotificationSeverity.php
│   ├── Ownership/
│   │   └── SystemOwnershipRepositoryInterface.php
│   ├── Security/
│   │   ├── Crypto/
│   │   │   └── CryptoKeyRingConfig.php
│   │   ├── CryptoContext.php
│   │   ├── Password/
│   │   │   ├── PasswordPepperRing.php
│   │   │   └── PasswordPepperRingConfig.php
│   │   ├── PermissionMapper.php
│   │   └── ScopeRegistry.php
│   ├── Service/
│   │   ├── AdminAuthenticationService.php
│   │   ├── AdminEmailVerificationService.php
│   │   ├── AdminNotificationRoutingService.php
│   │   ├── AuthorizationService.php
│   │   ├── PasswordService.php
│   │   ├── RecoveryStateService.php
│   │   ├── RememberMeService.php
│   │   ├── RoleAssignmentService.php
│   │   ├── RoleHierarchyComparator.php
│   │   ├── RoleLevelResolver.php
│   │   ├── SessionRevocationService.php
│   │   ├── SessionValidationService.php
│   │   ├── StepUpService.php
│   │   ├── TwoFactorEnrollmentService.php
│   │   ├── VerificationCodeGenerator.php
│   │   ├── VerificationCodePolicyResolver.php
│   │   └── VerificationCodeValidator.php
│   ├── Session/
│   │   └── Reader/
│   │       └── SessionListReaderInterface.php
│   └── Support/
│       └── CorrelationId.php
├── Http/
│   ├── Auth/
│   │   └── AuthSurface.php
│   ├── Controllers/
│   │   ├── AdminController.php
│   │   ├── AdminEmailVerificationController.php
│   │   ├── AdminNotificationHistoryController.php
│   │   ├── AdminNotificationPreferenceController.php
│   │   ├── AdminNotificationReadController.php
│   │   ├── AdminSecurityEventController.php
│   │   ├── AdminSelfAuditController.php
│   │   ├── AdminTargetedAuditController.php
│   │   ├── Api/
│   │   │   ├── AdminQueryController.php
│   │   │   ├── PermissionMetadataUpdateController.php
│   │   │   ├── PermissionsController.php
│   │   │   ├── Roles/
│   │   │   │   ├── RoleCreateController.php
│   │   │   │   ├── RoleMetadataUpdateController.php
│   │   │   │   ├── RoleRenameController.php
│   │   │   │   ├── RoleToggleController.php
│   │   │   │   └── RolesControllerQuery.php
│   │   │   ├── SessionBulkRevokeController.php
│   │   │   ├── SessionQueryController.php
│   │   │   └── SessionRevokeController.php
│   │   ├── AuthController.php
│   │   ├── NotificationQueryController.php
│   │   ├── StepUpController.php
│   │   ├── TelegramWebhookController.php
│   │   ├── Ui/
│   │   │   ├── ActivityLogListController.php
│   │   │   ├── SessionListController.php
│   │   │   ├── TelemetryListController.php
│   │   │   ├── UiAdminCreateController.php
│   │   │   ├── UiAdminsController.php
│   │   │   ├── UiDashboardController.php
│   │   │   ├── UiErrorController.php
│   │   │   ├── UiExamplesController.php
│   │   │   ├── UiLoginController.php
│   │   │   ├── UiPermissionsController.php
│   │   │   ├── UiRolesController.php
│   │   │   ├── UiSettingsController.php
│   │   │   ├── UiStepUpController.php
│   │   │   ├── UiTwoFactorSetupController.php
│   │   │   └── UiVerificationController.php
│   │   └── Web/
│   │       ├── ChangePasswordController.php
│   │       ├── DashboardController.php
│   │       ├── EmailVerificationController.php
│   │       ├── LoginController.php
│   │       ├── LogoutController.php
│   │       ├── TelegramConnectController.php
│   │       └── TwoFactorController.php
│   ├── Middleware/
│   │   ├── ActorContextMiddleware.php
│   │   ├── AdminContextMiddleware.php
│   │   ├── ApiGuestGuardMiddleware.php
│   │   ├── AuthorizationGuardMiddleware.php
│   │   ├── GuestGuardMiddleware.php
│   │   ├── HttpRequestTelemetryMiddleware.php
│   │   ├── RecoveryStateMiddleware.php
│   │   ├── RememberMeMiddleware.php
│   │   ├── RequestContextMiddleware.php
│   │   ├── RequestIdMiddleware.php
│   │   ├── ScopeGuardMiddleware.php
│   │   ├── SessionGuardMiddleware.php
│   │   ├── SessionStateGuardMiddleware.php
│   │   ├── UiRedirectNormalizationMiddleware.php
│   │   └── WebGuestGuardMiddleware.php
│   └── Routes/
│       └── AdminRoutes.php
├── Infrastructure/
│   ├── Admin/
│   │   └── Reader/
│   │       ├── PDOAdminBasicInfoReader.php
│   │       ├── PDOAdminEmailReader.php
│   │       └── PdoAdminProfileReader.php
│   ├── Audit/
│   │   ├── PdoAdminSecurityEventReader.php
│   │   ├── PdoAdminSelfAuditReader.php
│   │   ├── PdoAdminTargetedAuditReader.php
│   │   ├── PdoAuthoritativeAuditWriter.php
│   │   └── PdoTelemetryAuditLogger.php
│   ├── Context/
│   │   └── ActorContextProvider.php
│   ├── Crypto/
│   │   ├── AdminIdentifierCryptoService.php
│   │   ├── NotificationCryptoService.php
│   │   ├── PasswordCryptoService.php
│   │   └── TotpSecretCryptoService.php
│   ├── Database/
│   │   └── PDOFactory.php
│   ├── Logging/
│   │   ├── AuditTrailMaatifyAdapter.php
│   │   ├── AuthoritativeAuditMaatifyAdapter.php
│   │   ├── BehaviorTraceMaatifyAdapter.php
│   │   ├── DeliveryOperationsMaatifyAdapter.php
│   │   ├── DiagnosticsTelemetryMaatifyAdapter.php
│   │   └── SecuritySignalsMaatifyAdapter.php
│   ├── Notification/
│   │   └── TelegramHandler.php
│   ├── Query/
│   │   ├── ListFilterResolver.php
│   │   └── ResolvedListFilters.php
│   ├── Reader/
│   │   ├── Admin/
│   │   │   └── PdoAdminQueryReader.php
│   │   ├── PDOPermissionsReaderRepository.php
│   │   ├── PDORolesReaderRepository.php
│   │   └── Session/
│   │       └── PdoSessionListReader.php
│   ├── Repository/
│   │   ├── AdminActivityQueryRepository.php
│   │   ├── AdminEmailRepository.php
│   │   ├── AdminNotificationChannelRepository.php
│   │   ├── AdminNotificationPreferenceRepository.php
│   │   ├── AdminPasswordRepository.php
│   │   ├── AdminRepository.php
│   │   ├── AdminRoleRepository.php
│   │   ├── AdminSessionRepository.php
│   │   ├── AdminTotpSecretRepository.php
│   │   ├── FailedNotificationRepository.php
│   │   ├── NotificationReadRepository.php
│   │   ├── PdoAdminDirectPermissionRepository.php
│   │   ├── PdoAdminNotificationHistoryReader.php
│   │   ├── PdoAdminNotificationPersistenceRepository.php
│   │   ├── PdoAdminNotificationPreferenceRepository.php
│   │   ├── PdoAdminNotificationReadMarker.php
│   │   ├── PdoRememberMeRepository.php
│   │   ├── PdoStepUpGrantRepository.php
│   │   ├── PdoSystemOwnershipRepository.php
│   │   ├── PdoVerificationCodeRepository.php
│   │   ├── RedisStepUpGrantRepository.php
│   │   ├── RolePermissionRepository.php
│   │   └── Roles/
│   │       ├── PdoRoleCreateRepository.php
│   │       └── PdoRoleRepository.php
│   ├── Service/
│   │   ├── AdminTotpSecretStore.php
│   │   └── Google2faTotpService.php
│   ├── UX/
│   │   └── AdminActivityMapper.php
│   ├── Ui/
│   │   └── DefaultNavigationProvider.php
│   └── Updater/
│       └── PDOPermissionsMetadataRepository.php
├── Kernel/
│   └── AdminKernel.php
└── Modules/
    ├── AuditTrail/
    │   ├── CANONICAL_ARCHITECTURE.md
    │   ├── CHECKLIST.md
    │   ├── Contract/
    │   │   ├── AuditTrailLoggerInterface.php
    │   │   ├── AuditTrailPolicyInterface.php
    │   │   └── AuditTrailQueryInterface.php
    │   ├── DTO/
    │   │   ├── AuditTrailQueryDTO.php
    │   │   ├── AuditTrailRecordDTO.php
    │   │   └── AuditTrailViewDTO.php
    │   ├── Database/
    │   │   └── schema.audit_trail.sql
    │   ├── Enum/
    │   │   └── AuditTrailActorTypeEnum.php
    │   ├── Exception/
    │   │   └── AuditTrailStorageException.php
    │   ├── Infrastructure/
    │   │   └── Mysql/
    │   │       ├── AuditTrailLoggerMysqlRepository.php
    │   │       └── AuditTrailQueryMysqlRepository.php
    │   ├── PUBLIC_API.md
    │   ├── README.md
    │   ├── Recorder/
    │   │   ├── AuditTrailDefaultPolicy.php
    │   │   └── AuditTrailRecorder.php
    │   ├── Services/
    │   │   ├── ClockInterface.php
    │   │   └── SystemClock.php
    │   └── TESTING_STRATEGY.md
    ├── AuthoritativeAudit/
    │   ├── Contract/
    │   │   ├── AuthoritativeAuditOutboxWriterInterface.php
    │   │   └── AuthoritativeAuditPolicyInterface.php
    │   ├── DTO/
    │   │   └── AuthoritativeAuditOutboxWriteDTO.php
    │   ├── Database/
    │   │   └── schema.authoritative_audit.sql
    │   ├── Enum/
    │   │   ├── AuthoritativeAuditActorTypeInterface.php
    │   │   └── AuthoritativeAuditRiskLevelEnum.php
    │   ├── Exception/
    │   │   └── AuthoritativeAuditStorageException.php
    │   ├── Infrastructure/
    │   │   └── Mysql/
    │   │       └── AuthoritativeAuditOutboxWriterMysqlRepository.php
    │   ├── README.md
    │   ├── Recorder/
    │   │   ├── AuthoritativeAuditDefaultPolicy.php
    │   │   └── AuthoritativeAuditRecorder.php
    │   └── Services/
    │       ├── ClockInterface.php
    │       └── SystemClock.php
    ├── BehaviorTrace/
    │   ├── CANONICAL_ARCHITECTURE.md
    │   ├── CHECKLIST.md
    │   ├── Contract/
    │   │   ├── BehaviorTracePolicyInterface.php
    │   │   ├── BehaviorTraceQueryInterface.php
    │   │   └── BehaviorTraceWriterInterface.php
    │   ├── DTO/
    │   │   ├── BehaviorTraceContextDTO.php
    │   │   ├── BehaviorTraceCursorDTO.php
    │   │   └── BehaviorTraceEventDTO.php
    │   ├── Database/
    │   │   └── schema.behavior_trace.sql
    │   ├── Enum/
    │   │   ├── BehaviorTraceActorTypeEnum.php
    │   │   └── BehaviorTraceActorTypeInterface.php
    │   ├── Exception/
    │   │   └── BehaviorTraceStorageException.php
    │   ├── Infrastructure/
    │   │   └── Mysql/
    │   │       ├── BehaviorTraceQueryMysqlRepository.php
    │   │       └── BehaviorTraceWriterMysqlRepository.php
    │   ├── PUBLIC_API.md
    │   ├── README.md
    │   ├── Recorder/
    │   │   ├── BehaviorTraceDefaultPolicy.php
    │   │   └── BehaviorTraceRecorder.php
    │   ├── Services/
    │   │   ├── ClockInterface.php
    │   │   └── SystemClock.php
    │   └── TESTING_STRATEGY.md
    ├── Crypto/
    │   ├── DX/
    │   │   ├── CryptoContextFactory.php
    │   │   ├── CryptoDirectFactory.php
    │   │   ├── CryptoProvider.php
    │   │   ├── README.md
    │   │   └── docs/
    │   │       ├── ADR-005-Crypto-DX-Layer.md
    │   │       └── HOW_TO_USE.md
    │   ├── HKDF/
    │   │   ├── Exceptions/
    │   │   │   ├── HKDFException.php
    │   │   │   ├── InvalidContextException.php
    │   │   │   ├── InvalidOutputLengthException.php
    │   │   │   └── InvalidRootKeyException.php
    │   │   ├── HKDFContext.php
    │   │   ├── HKDFKeyDeriver.php
    │   │   ├── HKDFPolicy.php
    │   │   ├── HKDFService.php
    │   │   ├── HOW_TO_USE.md
    │   │   ├── README.md
    │   │   └── docs/
    │   │       └── ADR-003-HKDF.md
    │   ├── HOW_TO_USE.md
    │   ├── KeyRotation/
    │   │   ├── CryptoKeyInterface.php
    │   │   ├── DTO/
    │   │   │   ├── CryptoKeyDTO.php
    │   │   │   ├── KeyRotationDecisionDTO.php
    │   │   │   ├── KeyRotationStateDTO.php
    │   │   │   └── KeyRotationValidationResultDTO.php
    │   │   ├── Exceptions/
    │   │   │   ├── DecryptionKeyNotAllowedException.php
    │   │   │   ├── KeyNotFoundException.php
    │   │   │   ├── KeyRotationException.php
    │   │   │   ├── MultipleActiveKeysException.php
    │   │   │   └── NoActiveKeyException.php
    │   │   ├── KeyProviderInterface.php
    │   │   ├── KeyRotationPolicyInterface.php
    │   │   ├── KeyRotationService.php
    │   │   ├── KeyStatusEnum.php
    │   │   ├── Policy/
    │   │   │   └── StrictSingleActiveKeyPolicy.php
    │   │   ├── Providers/
    │   │   │   └── InMemoryKeyProvider.php
    │   │   ├── README.md
    │   │   └── docs/
    │   │       ├── ADR-002-Key-Rotation-Architecture.md
    │   │       └── HOW_TO_USE.md
    │   ├── Password/
    │   │   ├── DTO/
    │   │   │   └── ArgonPolicyDTO.php
    │   │   ├── Exception/
    │   │   │   ├── HashingFailedException.php
    │   │   │   ├── InvalidArgonPolicyException.php
    │   │   │   ├── PasswordCryptoException.php
    │   │   │   └── PepperUnavailableException.php
    │   │   ├── HOW_TO_USE.md
    │   │   ├── PasswordHasher.php
    │   │   ├── PasswordHasherInterface.php
    │   │   ├── Pepper/
    │   │   │   └── PasswordPepperProviderInterface.php
    │   │   ├── README.md
    │   │   └── docs/
    │   │       └── ADR-004-Password-Hashing-Architecture.md
    │   ├── README.md
    │   └── Reversible/
    │       ├── Algorithms/
    │       │   └── Aes256GcmAlgorithm.php
    │       ├── DTO/
    │       │   ├── ReversibleCryptoEncryptionResultDTO.php
    │       │   └── ReversibleCryptoMetadataDTO.php
    │       ├── Exceptions/
    │       │   ├── CryptoAlgorithmNotSupportedException.php
    │       │   ├── CryptoDecryptionFailedException.php
    │       │   └── CryptoKeyNotFoundException.php
    │       ├── README.md
    │       ├── Registry/
    │       │   └── ReversibleCryptoAlgorithmRegistry.php
    │       ├── ReversibleCryptoAlgorithmEnum.php
    │       ├── ReversibleCryptoAlgorithmInterface.php
    │       ├── ReversibleCryptoService.php
    │       └── docs/
    │           ├── ADR-001-Reversible-Crypto-Design.md
    │           └── HOW_TO_USE.md
    ├── DeliveryOperations/
    │   ├── Contract/
    │   │   ├── DeliveryOperationsLoggerInterface.php
    │   │   └── DeliveryOperationsPolicyInterface.php
    │   ├── DTO/
    │   │   └── DeliveryOperationRecordDTO.php
    │   ├── Database/
    │   │   └── schema.delivery_operations.sql
    │   ├── Enum/
    │   │   ├── DeliveryActorTypeInterface.php
    │   │   ├── DeliveryChannelEnum.php
    │   │   ├── DeliveryOperationTypeEnum.php
    │   │   └── DeliveryStatusEnum.php
    │   ├── Exception/
    │   │   └── DeliveryOperationsStorageException.php
    │   ├── Infrastructure/
    │   │   └── Mysql/
    │   │       └── DeliveryOperationsLoggerMysqlRepository.php
    │   ├── README.md
    │   ├── Recorder/
    │   │   ├── DeliveryOperationsDefaultPolicy.php
    │   │   └── DeliveryOperationsRecorder.php
    │   └── Services/
    │       ├── ClockInterface.php
    │       └── SystemClock.php
    ├── DiagnosticsTelemetry/
    │   ├── CANONICAL_ARCHITECTURE.md
    │   ├── CHECKLIST.md
    │   ├── Contract/
    │   │   ├── DiagnosticsTelemetryLoggerInterface.php
    │   │   ├── DiagnosticsTelemetryPolicyInterface.php
    │   │   └── DiagnosticsTelemetryQueryInterface.php
    │   ├── DTO/
    │   │   ├── DiagnosticsTelemetryContextDTO.php
    │   │   ├── DiagnosticsTelemetryCursorDTO.php
    │   │   └── DiagnosticsTelemetryEventDTO.php
    │   ├── Database/
    │   │   └── schema.diagnostics_telemetry.sql
    │   ├── Enum/
    │   │   ├── DiagnosticsTelemetryActorTypeEnum.php
    │   │   ├── DiagnosticsTelemetryActorTypeInterface.php
    │   │   ├── DiagnosticsTelemetrySeverityEnum.php
    │   │   └── DiagnosticsTelemetrySeverityInterface.php
    │   ├── Exception/
    │   │   └── DiagnosticsTelemetryStorageException.php
    │   ├── Infrastructure/
    │   │   └── Mysql/
    │   │       ├── DiagnosticsTelemetryLoggerMysqlRepository.php
    │   │       └── DiagnosticsTelemetryQueryMysqlRepository.php
    │   ├── OPEN_QUESTIONS.md
    │   ├── PUBLIC_API.md
    │   ├── README.md
    │   ├── Recorder/
    │   │   ├── DiagnosticsTelemetryDefaultPolicy.php
    │   │   └── DiagnosticsTelemetryRecorder.php
    │   ├── Services/
    │   │   ├── ClockInterface.php
    │   │   └── SystemClock.php
    │   └── TESTING_STRATEGY.md
    ├── Email/
    │   ├── Config/
    │   │   └── EmailTransportConfigDTO.php
    │   ├── DTO/
    │   │   ├── GenericEmailPayload.php
    │   │   └── RenderedEmailDTO.php
    │   ├── Exception/
    │   │   ├── EmailQueueWriteException.php
    │   │   ├── EmailRenderException.php
    │   │   └── EmailTransportException.php
    │   ├── Queue/
    │   │   ├── DTO/
    │   │   │   └── EmailQueuePayloadDTO.php
    │   │   ├── EmailQueueWriterInterface.php
    │   │   └── PdoEmailQueueWriter.php
    │   ├── Renderer/
    │   │   ├── EmailRendererInterface.php
    │   │   └── TwigEmailRenderer.php
    │   ├── Transport/
    │   │   ├── EmailTransportInterface.php
    │   │   └── SmtpEmailTransport.php
    │   ├── Worker/
    │   │   └── EmailQueueWorker.php
    │   └── docs/
    │       └── ADR-008-Email-Delivery-Independent-Queue.md
    ├── InputNormalization/
    │   ├── Contracts/
    │   │   └── InputNormalizerInterface.php
    │   ├── Middleware/
    │   │   └── InputNormalizationMiddleware.php
    │   ├── Normalizer/
    │   │   └── InputNormalizer.php
    │   └── docs/
    │       └── ADR-006-input-normalization.md
    ├── SecuritySignals/
    │   ├── CANONICAL_ARCHITECTURE.md
    │   ├── CHECKLIST.md
    │   ├── Contract/
    │   │   ├── SecuritySignalsLoggerInterface.php
    │   │   └── SecuritySignalsPolicyInterface.php
    │   ├── DTO/
    │   │   └── SecuritySignalRecordDTO.php
    │   ├── Database/
    │   │   └── schema.security_signals.sql
    │   ├── Enum/
    │   │   ├── SecuritySignalActorTypeEnum.php
    │   │   └── SecuritySignalSeverityEnum.php
    │   ├── Exception/
    │   │   └── SecuritySignalsStorageException.php
    │   ├── Infrastructure/
    │   │   └── Mysql/
    │   │       └── SecuritySignalsLoggerMysqlRepository.php
    │   ├── PUBLIC_API.md
    │   ├── README.md
    │   ├── Recorder/
    │   │   ├── SecuritySignalsDefaultPolicy.php
    │   │   └── SecuritySignalsRecorder.php
    │   ├── Services/
    │   │   ├── ClockInterface.php
    │   │   └── SystemClock.php
    │   └── TESTING_STRATEGY.md
    ├── SharedCommon/
    │   └── Contracts/
    │       ├── SecurityEventContextInterface.php
    │       └── TelemetryContextInterface.php
    ├── Telegram/
    │   └── docs/
    │       └── ADR-009-Telegram-Delivery-Independent-Queue.md
    └── Validation/
        ├── Contracts/
        │   ├── ErrorMapperInterface.php
        │   ├── SchemaInterface.php
        │   ├── SystemErrorMapperInterface.php
        │   └── ValidatorInterface.php
        ├── DTO/
        │   ├── ApiErrorResponseDTO.php
        │   └── ValidationResultDTO.php
        ├── Enum/
        │   ├── AuthErrorCodeEnum.php
        │   ├── HttpStatusCodeEnum.php
        │   └── ValidationErrorCodeEnum.php
        ├── ErrorMapper/
        │   ├── ApiErrorMapper.php
        │   └── SystemApiErrorMapper.php
        ├── Exceptions/
        │   ├── SchemaNotFoundException.php
        │   └── ValidationFailedException.php
        ├── Guard/
        │   └── ValidationGuard.php
        ├── HOW_TO_USE.md
        ├── HOW_TO_USE_GUARDS.md
        ├── README.md
        ├── Rules/
        │   ├── CredentialInputRule.php
        │   ├── DateRangeRule.php
        │   ├── EmailRule.php
        │   ├── PaginationRule.php
        │   ├── PasswordRule.php
        │   ├── RequiredStringRule.php
        │   └── SearchQueryRule.php
        ├── Schemas/
        │   ├── AbstractSchema.php
        │   ├── AdminAddEmailSchema.php
        │   ├── AdminCreateSchema.php
        │   ├── AdminEmailVerifySchema.php
        │   ├── AdminGetEmailSchema.php
        │   ├── AdminListSchema.php
        │   ├── AdminLookupEmailSchema.php
        │   ├── AdminNotificationHistorySchema.php
        │   ├── AdminNotificationReadSchema.php
        │   ├── AdminPreferenceGetSchema.php
        │   ├── AdminPreferenceUpsertSchema.php
        │   ├── AuthLoginSchema.php
        │   ├── NotificationQuerySchema.php
        │   ├── PermissionMetadataUpdateSchema.php
        │   ├── Roles/
        │   │   ├── RoleCreateSchema.php
        │   │   ├── RoleMetadataUpdateSchema.php
        │   │   ├── RoleRenameSchema.php
        │   │   └── RoleToggleSchema.php
        │   ├── SessionBulkRevokeSchema.php
        │   ├── SessionRevokeSchema.php
        │   ├── SharedListQuerySchema.php
        │   ├── StepUpVerifySchema.php
        │   └── TelegramWebhookSchema.php
        └── Validator/
            └── RespectValidator.php
routes/
└── web.php
templates/
├── 2fa-setup.twig
├── 2fa-verify.twig
├── auth/
│   ├── 2fa_setup.twig
│   └── change_password.twig
├── dashboard.twig
├── emails/
│   ├── layouts/
│   │   └── base.twig
│   ├── otp/
│   │   ├── ar.twig
│   │   └── en.twig
│   └── verification/
│       ├── ar.twig
│       └── en.twig
├── examples/
│   └── main.twig
├── layout.twig
├── layouts/
│   └── base.twig
├── login.twig
├── pages/
│   ├── 2fa_verify.twig
│   ├── activity_logs.twig
│   ├── admin/
│   │   ├── email.list.twig
│   │   └── sessions.list.twig
│   ├── admins.twig
│   ├── admins_create.twig
│   ├── admins_profile.twig
│   ├── admins_profile_edit.twig
│   ├── dashboard.twig
│   ├── error.twig
│   ├── examples.twig
│   ├── login.twig
│   ├── permissions.twig
│   ├── roles.twig
│   ├── sessions.twig
│   ├── settings.twig
│   ├── telemetry_list.twig
│   ├── telemetry_metadata.twig
│   └── verify_email.twig
├── telegram-connect.twig
└── verify-email.twig
public/
├── assets/
│   ├── css/
│   │   ├── forms.css
│   │   ├── style.css
│   │   └── table.css
│   ├── images/
│   │   ├── bg.png*
│   │   ├── login-bg.webp
│   │   ├── login_model.webp
│   │   ├── logo-small.png
│   │   ├── logo.png
│   │   └── pdf_logo.png
│   └── js/
│       ├── Input_checker.js*
│       ├── callback_handler.js*
│       ├── data_table.js
│       ├── pages/
│       │   ├── activity_logs.js
│       │   ├── admin_emails.js
│       │   ├── admin_sessions.js
│       │   ├── admins-list.js
│       │   ├── admins_create.js
│       │   ├── permissions.js
│       │   ├── roles-core.js
│       │   ├── roles-create-rename-toggle.js
│       │   ├── roles-events.js
│       │   ├── roles-metadata.js
│       │   ├── sessions.js
│       │   └── telemetry_list.js
│       └── select2.js
├── favicon.ico
└── index.php
docs/
├── ADMIN_PANEL_CANONICAL_TEMPLATE.md
├── API/
│   └── ROLES_TOGGLE.md
├── API_PHASE1.md
├── AUDIT_COMMIT_HISTORY.md
├── KERNEL_BOOTSTRAP.md
├── KERNEL_BOUNDARIES.md
├── ONBOARDING-AR.md
├── ONBOARDING.md
├── PHASE_1_CLOSURE_REPORT.md
├── PROJECT_CANONICAL_CONTEXT.md
├── PROJECT_FILE_STRUCTURE.md
├── PROJECT_TASK_CHECKLIST.md
├── UI_APPLICATION_BOUNDARY_REPORT.md
├── UI_EXTENSION.md
├── adr/
│   ├── ADR-001-Reversible-Crypto-Design.md
│   ├── ADR-002-Key-Rotation-Architecture.md
│   ├── ADR-003-HKDF.md
│   ├── ADR-004-Password-Hashing-Architecture.md
│   ├── ADR-005-Crypto-DX-Layer.md
│   ├── ADR-006-input-normalization.md
│   ├── ADR-007-notification-scope-and-history-coupling.md
│   ├── ADR-008-Email-Delivery-Independent-Queue.md
│   ├── ADR-009-Telegram-Delivery-Independent-Queue.md
│   ├── ADR-010-Crypto-Key-Rotation-Wiring.md
│   ├── ADR-011-data-access-logs.md
│   ├── ADR-012-unified-verification-codes.md
│   ├── ADR-013-test-rbac-seeding-exception.md
│   ├── ADR-014-verification-notification-dispatcher.md
│   └── README.md
├── architecture/
│   ├── ARCHITECTURAL_CONFLICT_RESOLUTION_POLICY.md
│   ├── analysis/
│   │   └── validation-schema-contract-analysis.md
│   ├── audit-model.md
│   ├── input-validation.md
│   ├── logging/
│   │   ├── ASCII_FLOW_LEGENDS.md
│   │   ├── CANONICAL_LOGGER_DESIGN_STANDARD.md
│   │   ├── GLOBAL_LOGGING_RULES.md
│   │   ├── LOGGING_ASCII_OVERVIEW.md
│   │   ├── LOGGING_LIBRARY_STRUCTURE_CANONICAL.md
│   │   ├── LOGGING_MODULE_BLUEPRINT.md
│   │   ├── LOG_DOMAINS_OVERVIEW.md
│   │   ├── LOG_STORAGE_AND_ARCHIVING.md
│   │   ├── README.md
│   │   ├── UNIFIED_LOGGING_DESIGN.md
│   │   ├── unified-logging-system.ar.md
│   │   └── unified-logging-system.en.md
│   ├── notification/
│   │   ├── channel-preference-resolution.md
│   │   └── multi-channel-resolution-rules.md
│   ├── notification-delivery.md
│   ├── notification-routing.md
│   ├── phase8-observability.md
│   └── security/
│       └── PERMISSION_STRATEGY.md
├── audits/
│   ├── PHASE_14_READINESS_OPTIMIZATION_AUDIT.md
│   ├── PHASE_C4_SPEC_CONFORMANCE_REPORT.md
│   └── UNUSED_CODE_INVENTORY.md
├── auth/
│   ├── auth-flow.md
│   ├── failure-semantics.md
│   ├── remember-me.md
│   └── step-up-matrix.md
├── index.ai.md
├── index.md
├── phases/
│   ├── PHASE_SESSIONS_COMPLETE.md
│   ├── phase13.7.md
│   ├── phase8.lock.md
│   └── phase9.2.md
├── refactor/
│   └── REFACTOR_PLAN_CRYPTO_AND_DB_CENTRALIZATION.md
├── security/
│   ├── authentication-architecture.md
│   ├── phase-c2.1-auth-review.md
│   └── system-ownership.md
├── telemetry-logging.md
├── tests/
│   ├── canonical-admins-query.test-plan.md
│   ├── canonical-list-query.as-is-map.md
│   └── canonical-sessions-query.test-plan.md
└── ui/
    └── js/
        ├── SELECT2.md
        └── data_table/
            ├── README.md
            └── README_AR.md

225 directories, 716 files
