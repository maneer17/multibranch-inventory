Good idea to pin these down before diving into migrations — here's a glossary for the terms I've been using.

**Stock (المخزون)**
The quantity of a product physically available at a location right now. "Stock" and "inventory" are used interchangeably. In Arabic: **المخزون** — الكمية المتوفرة فعليًا من منتج معين في مكان معين.

**Ledger (السجل / دفتر القيد)**
A chronological, append-only record of every transaction that changed something — you never edit or delete an entry, you only ever add new ones. It comes from accounting: a bank ledger records every deposit/withdrawal rather than just showing you a final balance you can edit directly. In our system, `stock_movements` is the ledger — every stock change (received, sold, transferred, adjusted) is a new row, and the current quantity is *derived* by summing the ledger, not edited directly.
In Arabic: **السجل** — سجل متسلسل لكل عملية غيّرت شيئًا (مثل الكمية)، لا تُعدَّل فيه القيود القديمة، فقط تُضاف قيود جديدة. الرصيد الحالي يُحسب من مجموع هذه القيود.

**Transfer (نقل / تحويل بضاعة)**
Moving stock from one branch to another (not a sale, not a purchase — internal movement). A `transfer_order` is the document/request that tracks this move through its stages (requested → approved → in transit → received).
In Arabic: **التحويل بين الفروع** — نقل كمية من منتج من فرع إلى فرع آخر، ويُسجَّل كمستند يمر بحالات (طلب، موافقة، قيد النقل، تم الاستلام).

**SKU (رمز تخزين الوحدة / كود المنتج)**
"Stock Keeping Unit" — a unique code identifying one specific product variant (e.g. "T-SHIRT-RED-L" for a red, large T-shirt, as distinct from a blue one or a medium one). It's how systems track individual product variants without ambiguity.
In Arabic: **رمز تخزين الوحدة (SKU)** — كود فريد يُعرّف منتجًا معينًا بدقة (بما في ذلك خصائصه مثل اللون والمقاس)، يُستخدم لتمييزه عن باقي المنتجات في النظام.

**Reorder point / ROP (نقطة إعادة الطلب)**
The stock level at which you should place a new order, calculated so the new stock arrives before you run out.
In Arabic: **نقطة إعادة الطلب** — المستوى الذي إذا وصل إليه المخزون، يجب طلب كمية جديدة، بحيث تصل الكمية الجديدة قبل نفاد المخزون تمامًا.

**Safety stock (مخزون الأمان)**
Extra buffer stock held beyond expected need, to absorb unexpected demand spikes or supplier delays.
In Arabic: **مخزون الأمان** — كمية احتياطية إضافية تُحفظ لمواجهة أي زيادة غير متوقعة في الطلب أو تأخر من المورد.

**Purchase Order / PO (أمر شراء)**
A formal document sent to a supplier requesting specific products and quantities — it's the request; "receiving" is what happens when the goods actually arrive and get added to stock.
In Arabic: **أمر الشراء** — مستند رسمي يُرسل للمورّد لطلب منتجات بكميات محددة؛ "الاستلام" هو ما يحدث لاحقًا عند وصول البضاعة فعليًا وإضافتها للمخزون.

**Reconciliation (تسوية / مطابقة)**
The process of checking whether two records that should agree actually do (e.g., does the cached `quantity` column match what the ledger says it should be?), and correcting any mismatch.
In Arabic: **التسوية/المطابقة** — عملية التحقق من تطابق رقمين يُفترض أن يتساويا (مثل الرصيد المخزَّن مقابل ما يقوله السجل)، وتصحيح أي فرق بينهما.

**Polymorphic relation (علاقة متعددة الأشكال)**
A database technique (specific to Laravel/ORMs) letting one table (`stock_movements`) reference *different types* of other tables (sometimes a `purchase_order`, sometimes a `transfer_order`) through a single generic pair of columns (`reference_type`, `reference_id`) instead of needing a separate foreign key column for each possible type.
In Arabic: **العلاقة متعددة الأشكال** — أسلوب في قواعد البيانات يسمح لجدول واحد بالارتباط بأنواع مختلفة من الجداول الأخرى (أحيانًا أمر شراء، وأحيانًا تحويل بضاعة) عبر عمودين عامّين بدلاً من عمود مفتاح خارجي منفصل لكل نوع.

Want me to keep using these English terms going forward, or would it help if I used the Arabic terms alongside the English ones consistently through the rest of the planning?
