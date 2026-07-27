# ── Flutter / embedding ──────────────────────────────────────────────────────
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }
-dontwarn io.flutter.embedding.**

# ── Firebase (Core / Messaging / Analytics) ─────────────────────────────────
-keep class com.google.firebase.** { *; }
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.firebase.**
-dontwarn com.google.android.gms.**

# ── Kotlin / coroutines ─────────────────────────────────────────────────────
-keepclassmembers class kotlinx.** { *; }
-dontwarn kotlinx.**

# ── Keep annotations & signatures used for reflection/serialization ─────────
-keepattributes Signature
-keepattributes *Annotation*
-keepattributes SourceFile,LineNumberTable

# ── App entry points referenced from native (FCM background isolate) ────────
-keep class com.cashnest.app.** { *; }

# ── Suppress notes for optional/absent classes ──────────────────────────────
-dontwarn javax.annotation.**
