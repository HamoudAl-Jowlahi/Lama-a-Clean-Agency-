/* نموذج تفاعلي للوحة الإدارة — بيانات توضيحية فقط */

const ST = { pending: 'قيد المراجعة', confirmed: 'مؤكد', assigned: 'تم الإسناد', on_the_way: 'في الطريق', in_progress: 'جارٍ التنفيذ', completed: 'مكتمل', cancelled: 'ملغي', rejected: 'مرفوض' };
const CST = { open: 'مفتوحة', under_review: 'قيد المراجعة', resolved: 'تم الحل', closed: 'مغلقة' };
const PST = { due: 'مستحق', collected: 'تم التحصيل', waived: 'معفى' };
const KST = { pending: 'قيد المراجعة', confirmed: 'مؤكد', assigned: 'تم الإسناد', active: 'ساري', completed: 'منتهٍ', terminated: 'أُنهي مبكراً', cancelled: 'ملغي', rejected: 'مرفوض' };
const RST = { open: 'جديد', under_review: 'قيد المراجعة', approved: 'معتمد', rejected: 'مرفوض' };
const CUR = 'ر.س'; // مثال — العملة من الإعدادات

const bookings = [
  { no: 'BK-2026-000131', c: 'نورة العتيبي', ph: '05x xxx 4410', svc: 'تنظيف شامل · فيلا', d: 'الأحد 4 أكتوبر', t: '08:00 ص', area: 'حي الربيع', w: null, st: 'pending', amt: 460, pay: 'due' },
  { no: 'BK-2026-000130', c: 'خالد الحربي', ph: '05x xxx 9021', svc: 'الكنب والسجاد', d: 'الأحد 4 أكتوبر', t: '12:00 م', area: 'حي الصحافة', w: null, st: 'pending', amt: 172.5, pay: 'due' },
  { no: 'BK-2026-000128', c: 'ريم القحطاني', ph: '05x xxx 1187', svc: 'تنظيف النوافذ', d: 'الأحد 4 أكتوبر', t: '04:00 م', area: 'حي العارض', w: null, st: 'confirmed', amt: 138, pay: 'collected' },
  { no: 'BK-2026-000125', c: 'سارة أحمد', ph: '05x xxx 1234', svc: 'الكنب والسجاد', d: 'الأحد 4 أكتوبر', t: '02:00 م', area: 'حي الياسمين', w: 'فريق ب', st: 'assigned', amt: 172.5, pay: 'due' },
  { no: 'BK-2026-000123', c: 'سارة أحمد', ph: '05x xxx 1234', svc: 'تنظيف شامل · شقة', d: 'الأحد 4 أكتوبر', t: '10:00 ص', area: 'حي النرجس', w: 'فريق أ', st: 'on_the_way', amt: 287.5, pay: 'collected' },
  { no: 'BK-2026-000122', c: 'منى الشهري', ph: '05x xxx 7730', svc: 'تنظيف المكاتب', d: 'الأحد 4 أكتوبر', t: '08:00 ص', area: 'حي العليا', w: 'فريق ب', st: 'in_progress', amt: 345, pay: 'collected' },
  { no: 'BK-2026-000118', c: 'عبدالله السبيعي', ph: '05x xxx 5502', svc: 'تنظيف شامل · شقة', d: 'السبت 3 أكتوبر', t: '10:00 ص', area: 'حي الملقا', w: 'فريق ب', st: 'completed', amt: 287.5, pay: 'due' },
  { no: 'BK-2026-000116', c: 'هند الدوسري', ph: '05x xxx 3318', svc: 'تنظيف النوافذ', d: 'السبت 3 أكتوبر', t: '06:00 م', area: 'حي النخيل', w: null, st: 'cancelled', amt: 138, pay: 'due' },
  { no: 'BK-2026-000112', c: 'فهد المطيري', ph: '05x xxx 6604', svc: 'تنظيف شامل · فيلا', d: 'الجمعة 2 أكتوبر', t: '08:00 ص', area: 'حي حطين', w: null, st: 'rejected', amt: 460, pay: 'due' },
];
// CR-3: نوعان — housekeeper (خادمة، عقود فقط) و cleaner (عضو فريق زيارات)
const workers = [
  { n: 'فاطمة', type: 'housekeeper', ph: '05x xxx 2210', st: 'active', rate: 4.8, cnt: 26, busy: null },
  { n: 'مريم', type: 'housekeeper', ph: '05x xxx 8831', st: 'active', rate: 4.6, cnt: 18, busy: 'في عقد ساري حتى 30 نوفمبر' },
  { n: 'عائشة', type: 'housekeeper', ph: '05x xxx 4127', st: 'active', rate: 4.9, cnt: 38, busy: 'في عقد ساري حتى 8 نوفمبر' },
  { n: 'خديجة', type: 'housekeeper', ph: '05x xxx 5190', st: 'active', rate: 4.7, cnt: 12, busy: null },
  { n: 'زينب', type: 'housekeeper', ph: '05x xxx 9975', st: 'on_leave', rate: 4.5, cnt: 9 },
  { n: 'حليمة', type: 'housekeeper', ph: '05x xxx 3006', st: 'inactive', rate: 4.2, cnt: 4 },
  { n: 'هدى', type: 'cleaner', team: 'فريق أ', ph: '05x xxx 7001', st: 'active', rate: 4.8, cnt: 210 },
  { n: 'سلمى', type: 'cleaner', team: 'فريق أ', ph: '05x xxx 7002', st: 'active', rate: 4.8, cnt: 210 },
  { n: 'رحاب', type: 'cleaner', team: 'فريق أ', ph: '05x xxx 7003', st: 'active', rate: 4.8, cnt: 205 },
  { n: 'منيرة', type: 'cleaner', team: 'فريق ب', ph: '05x xxx 7004', st: 'active', rate: 4.6, cnt: 180 },
  { n: 'جميلة', type: 'cleaner', team: 'فريق ب', ph: '05x xxx 7005', st: 'active', rate: 4.6, cnt: 176 },
  { n: 'أمل', type: 'cleaner', team: 'فريق ب', ph: '05x xxx 6021', st: 'active', rate: 4.6, cnt: 150 },
];
const WT = { cleaner: 'فريق الزيارات', housekeeper: 'خادمة' };
const teams = [
  { n: 'فريق أ', leader: 'هدى', members: ['هدى', 'سلمى', 'رحاب'], active: true, today: 2, rate: 4.8, busy: 'لديه زيارة 10:00 ص' },
  { n: 'فريق ب', leader: 'منيرة', members: ['منيرة', 'جميلة', 'أمل'], active: true, today: 3, rate: 4.6, busy: null },
  { n: 'فريق ج', leader: null, members: [], active: false, today: 0, rate: null, busy: 'غير مفعّل' },
];
const complaints = [
  { no: 'CM-000047', c: 'عبدالله السبيعي', bk: 'BK-2026-000118', type: 'جودة الخدمة', st: 'open', at: 'منذ ساعة' },
  { no: 'CM-000045', c: 'سارة أحمد', bk: 'BK-2026-000123', type: 'تأخر عن الموعد', st: 'under_review', at: 'منذ يومين' },
  { no: 'CM-000031', c: 'منى الشهري', bk: 'BK-2026-000122', type: 'سلوك', st: 'resolved', at: '12 سبتمبر' },
  { no: 'CM-000020', c: 'خالد الحربي', bk: 'BK-2026-000130', type: 'مشكلة في الدفع', st: 'closed', at: '2 أغسطس' },
];

const contracts = [
  { no: 'CT-2026-000016', c: 'نورة العتيبي', ph: '05x xxx 4410', plan: 'دوام كامل', m: 3, from: '15 أكتوبر', to: '14 يناير', area: 'حي الربيع', w: null, st: 'pending', monthly: 1800 },
  { no: 'CT-2026-000015', c: 'ريم القحطاني', ph: '05x xxx 1187', plan: 'نصف يوم', m: 1, from: '12 أكتوبر', to: '11 نوفمبر', area: 'حي العارض', w: null, st: 'confirmed', monthly: 1200 },
  { no: 'CT-2026-000014', c: 'سارة أحمد', ph: '05x xxx 1234', plan: 'دوام كامل', m: 1, from: '10 أكتوبر', to: '8 نوفمبر', area: 'حي النرجس', w: 'عائشة', st: 'active', monthly: 1800,
    history: [['فاطمة', '10 أكتوبر', '12 أكتوبر', 'استُبدلت — جودة العمل'], ['عائشة', '14 أكتوبر', null, null]] },
  { no: 'CT-2026-000011', c: 'منى الشهري', ph: '05x xxx 7730', plan: 'دوام جزئي', m: 3, from: '1 سبتمبر', to: '30 نوفمبر', area: 'حي العليا', w: 'مريم', st: 'active', monthly: 1000,
    history: [['مريم', '1 سبتمبر', null, null]] },
  { no: 'CT-2026-000007', c: 'عبدالله السبيعي', ph: '05x xxx 5502', plan: 'دوام كامل', m: 1, from: '1 أغسطس', to: '30 أغسطس', area: 'حي الملقا', w: 'زينب', st: 'completed', monthly: 1800 },
  { no: 'CT-2026-000005', c: 'خالد الحربي', ph: '05x xxx 9021', plan: 'دوام كامل', m: 3, from: '1 يوليو', to: '18 أغسطس', area: 'حي الصحافة', w: 'حليمة', st: 'terminated', monthly: 1800 },
];
const requests = [
  { no: 'RQ-000033', ct: 'CT-2026-000011', c: 'منى الشهري', type: 'replace_worker', reason: 'تأخر متكرر', w: 'مريم', st: 'open', at: 'منذ 3 ساعات', details: 'تتأخر العاملة يومياً قرابة ساعة عن بداية الدوام.' },
  { no: 'RQ-000032', ct: 'CT-2026-000011', c: 'منى الشهري', type: 'terminate', reason: 'لم أعد بحاجة للخدمة', w: 'مريم', st: 'under_review', at: 'أمس', details: 'طلب إنهاء بتاريخ 31 أكتوبر.' },
  { no: 'RQ-000031', ct: 'CT-2026-000014', c: 'سارة أحمد', type: 'replace_worker', reason: 'جودة العمل', w: 'فاطمة', st: 'approved', at: '12 أكتوبر', details: 'المطبخ لا يُنظف بشكل جيد رغم التنبيه.' },
];
const RTYPE = { replace_worker: 'استبدال العاملة', terminate: 'إنهاء العقد' };

/* ============ Helpers ============ */
const money = v => v.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' ' + CUR;
const badge = (k, map = ST) => `<span class="badge badge-${k}">${map[k] || k}</span>`;
const ic = (n, cls = '') => `<svg class="i ${cls}"><use href="#i-${n}"/></svg>`;
const stars = n => `<span class="stars">${[1, 2, 3, 4, 5].map(i => `<svg class="i i-sm ${i <= n ? 'on' : 'off'}"><use href="#i-star"/></svg>`).join('')}</span>`;
const avatar = n => `<span class="avatar" style="width:30px;height:30px">${n[0]}</span>`;
const count = s => bookings.filter(b => b.st === s).length;
const search = (ph, w = 260) => `<div class="input input-sm" style="width:${w}px;max-width:100%">${ic('search', 'i-sm')}<input placeholder="${ph}"></div>`;
const table = (head, rows, o = {}) => `<div class="panel">${o.header || ''}<div class="tbl-wrap"><table class="tbl"><thead><tr>${head.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${rows}</tbody></table></div>${o.pager === false ? '' :
  `<div class="pager"><span>عرض 1–${o.n || 10} من ${o.total || o.n || 10}</span><span class="row" style="gap:4px"><button class="btn btn-secondary btn-sm">السابق</button><button class="btn btn-secondary btn-sm">التالي</button></span></div>`}</div>`;
const payCell = b => b.st === 'completed' ? badge(b.pay, PST) : ['cancelled', 'rejected'].includes(b.st) ? '<span class="faint">—</span>' : '<span class="t-sm muted">نقداً عند الإتمام</span>';
const panelH =(title, right = '') => `<div class="panel-h"><h2>${title}</h2>${right}</div>`;
const tg = on => `<span class="toggle ${on ? 'on' : ''}" role="switch" aria-checked="${!!on}"></span>`;

/* ============ Navigation ============ */
const NAV = [
  ['العمليات'],
  ['dashboard', 'لوحة التحكم', 'grid'],
  ['bookings', 'الطلبات', 'calendar', () => count('pending')],
  ['contracts', 'العقود', 'contract', () => contracts.filter(c => c.st === 'pending').length + requests.filter(r => r.st === 'open').length],
  ['assignments', 'إسنادات الزيارات', 'briefcase'],
  ['complaints', 'الشكاوى', 'message', () => complaints.filter(c => c.st === 'open').length],
  ['الأشخاص'],
  ['customers', 'العملاء', 'user'],
  ['teams', 'فرق الزيارات', 'users'],
  ['workers', 'الموظفون', 'shield'],
  ['الخدمات والمالية'],
  ['services', 'الخدمات', 'home2'],
  ['pricing', 'الأسعار', 'tag'],
  ['payments', 'التحصيل النقدي', 'cash'],
  ['ratings', 'التقييمات', 'star'],
  ['النظام'],
  ['notifications', 'الإشعارات', 'bell'],
  ['reports', 'التقارير', 'chart'],
  ['settings', 'الإعدادات', 'settings'],
];
function renderNav(active) {
  document.getElementById('nav').innerHTML = NAV.map(n => {
    if (n.length === 1) return `<div class="grp">${n[0]}</div>`;
    const c = n[3] ? n[3]() : 0;
    return `<a data-v="${n[0]}" class="${n[0] === active ? 'on' : ''}">${ic(n[2])}${n[1]}${c ? `<span class="cnt">${c}</span>` : ''}</a>`;
  }).join('');
}

/* ============ Views ============ */
const V = {};

V.dashboard = () => `
  <div class="kpis">
    <div class="kpi"><span class="t-sm muted">طلبات اليوم</span><div class="v num">14</div><span class="d">6 مكتملة · 3 جارية</span></div>
    <div class="kpi"><span class="t-sm muted">بانتظار المراجعة</span><div class="v num">${count('pending')}</div><span class="d">أقدمها منذ 42 دقيقة</span></div>
    <div class="kpi"><span class="t-sm muted">عقود سارية</span><div class="v num">${contracts.filter(k => k.st === 'active').length}</div><span class="d">${contracts.filter(k => k.st === 'pending').length} طلب عقد جديد</span></div>
    <div class="kpi"><span class="t-sm muted">طلبات استبدال / إنهاء</span><div class="v num">${requests.filter(r => ['open', 'under_review'].includes(r.st)).length}</div><span class="d">و ${complaints.filter(c => ['open', 'under_review'].includes(c.st)).length} شكاوى مفتوحة</span></div>
  </div>
  <div class="cols-2">
    ${table(['رقم الطلب', 'العميل', 'الموعد', 'الحالة', ''],
      bookings.filter(b => ['pending', 'confirmed'].includes(b.st)).map(b => `<tr class="click" data-bk="${b.no}"><td class="num"><b>${b.no}</b></td><td>${b.c}</td><td>${b.d}<span class="sub">${b.t}</span></td><td>${badge(b.st)}</td><td><button class="btn btn-secondary btn-sm">${b.st === 'pending' ? 'مراجعة' : 'إسناد'}</button></td></tr>`).join(''),
      { header: panelH('يحتاج إجراء', '<a class="t-sm" data-go="bookings" href="#">كل الطلبات</a>'), pager: false })}
    <div class="panel">${panelH('توزيع حالات اليوم')}<div class="panel-b">
      ${[['pending', 2, 'var(--n-400)'], ['confirmed', 1, 'var(--blue-300)'], ['assigned', 1, 'var(--blue-400)'], ['on_the_way', 1, 'var(--blue-500)'], ['in_progress', 3, 'var(--blue-700)'], ['completed', 6, 'var(--success-600)']]
        .map(([k, v, c]) => `<div class="bar-row"><span>${ST[k]}</span><div class="bar-track"><span style="width:${v / 6 * 100}%;background:${c}"></span></div><b class="num">${v}</b></div>`).join('')}
    </div></div>
  </div>
  <div class="cols-2">
    ${table(['الفريق', 'القائد', 'زيارات اليوم', 'الحالة الآن'],
      teams.map((t, i) => `<tr><td><b>${t.n}</b></td><td>${t.leader || '<span class="faint">—</span>'}</td><td class="num">${t.today}</td><td>${[badge('on_the_way'), badge('in_progress'), '<span class="badge badge-cancelled">غير مفعّل</span>'][i]}</td></tr>`).join(''),
      { header: panelH('الفرق اليوم', '<a class="t-sm" data-go="teams" href="#">كل الفرق</a>'), pager: false })}
    <div class="panel">${panelH('أحدث الشكاوى')}<div>
      ${complaints.slice(0, 3).map((c, i) => `<div class="c-item" data-c="${i}"><div class="row-between"><b class="t-sm num">${c.no}</b>${badge(c.st, CST)}</div><div class="t-sm">${c.type}</div><div class="t-xs muted">${c.c} · ${c.at}</div></div>`).join('')}
    </div></div>
  </div>`;

let bkFilter = 'all';
V.bookings = () => {
  const list = bookings.filter(b => bkFilter === 'all' || b.st === bkFilter);
  return `
  <div class="tabs">${['all', ...Object.keys(ST)].map(k => `<span class="chip ${bkFilter === k ? 'is-active' : ''}" data-f="${k}">${k === 'all' ? 'الكل' : ST[k]} <span class="n num">${k === 'all' ? bookings.length : count(k)}</span></span>`).join('')}</div>
  <div class="toolbar">
    ${search('رقم الطلب، العميل، الجوال')}
    <div class="input input-sm" style="width:170px">${ic('calendar', 'i-sm')}<input value="4 أكتوبر 2026"></div>
    <div class="input input-sm" style="width:160px"><select><option>كل الخدمات</option></select></div>
    <span class="grow"></span>
    <button class="btn btn-secondary btn-sm">${ic('download', 'i-sm')} تصدير</button>
  </div>
  ${list.length ? table(['رقم الطلب', 'العميل', 'الخدمة', 'الموعد', 'الفريق', 'الحالة', 'المبلغ', 'الدفع'],
    list.map(b => `<tr class="click" data-bk="${b.no}"><td class="num"><b>${b.no}</b></td><td>${b.c}<span class="sub">${b.area}</span></td><td>${b.svc}</td><td>${b.d}<span class="sub">${b.t}</span></td><td>${b.w || '<span class="faint">—</span>'}</td><td>${badge(b.st)}</td><td class="num">${money(b.amt)}</td><td>${payCell(b)}</td></tr>`).join(''),
    { n: list.length, total: list.length })
  : `<div class="panel state"><div class="state-icon">${ic('inbox')}</div><b>لا توجد طلبات بهذه الحالة</b><span class="t-sm muted">جرّب تغيير الفلتر أو التاريخ.</span></div>`}`;
};

V.assignments = () => `<div class="toolbar">${search('رقم الطلب أو الفريق')}<div class="input input-sm" style="width:160px"><select><option>كل حالات الإسناد</option></select></div></div>` +
  table(['الطلب', 'الفريق', 'أسندها', 'وقت الإسناد', 'حالة الإسناد', 'رد القائد / السبب'],
  [['BK-2026-000125', 'فريق ب', 'مدير العمليات', 'اليوم 8:10 ص', 'pending', '—'],
   ['BK-2026-000123', 'فريق أ', 'مدير العمليات', 'اليوم 7:40 ص', 'accepted', 'هدى · 7:52 ص'],
   ['BK-2026-000122', 'فريق ب', 'مدير العمليات', 'أمس 9:15 م', 'accepted', 'منيرة · 9:20 م'],
   ['BK-2026-000120', 'فريق أ', 'مدير العمليات', 'أمس 6:00 م', 'rejected', 'هدى · تعارض في الموعد'],
   ['BK-2026-000118', 'فريق ب', 'المدير العام', 'الجمعة 8:00 م', 'accepted', 'منيرة · 8:04 م']]
  .map(r => `<tr><td class="num"><b>${r[0]}</b></td><td><b>${r[1]}</b></td><td>${r[2]}</td><td>${r[3]}</td><td>${badge({ pending: 'pending', accepted: 'confirmed', rejected: 'rejected' }[r[4]], { pending: 'بانتظار القائد', confirmed: 'مقبول', rejected: 'مرفوض' })}</td><td class="t-sm muted">${r[5]}</td></tr>`).join(''), { n: 5, total: 5 });

// CR-3: فرق الزيارات — الطاقة الاستيعابية = عدد الفرق المفعّلة
V.teams = () => `<div class="alert alert-info" style="margin-bottom:16px">${ic('users')}الزيارة تُسند لفريق كامل، وقائده وحده يقبلها ويحدّث حالتها ويستلم المبلغ. عدد الزيارات الممكنة في نفس الوقت = عدد الفرق المفعّلة.</div>` +
  `<div class="toolbar"><span class="grow"></span><button class="btn btn-primary btn-sm">${ic('plus', 'i-sm')} فريق جديد</button></div>` +
  table(['الفريق', 'القائد', 'الأعضاء', 'زيارات اليوم', 'التقييم', 'مفعّل', ''],
  teams.map(t => `<tr><td><b>${t.n}</b></td><td>${t.leader ? `<div class="row">${avatar(t.leader)}${t.leader}</div>` : '<span class="faint">بلا قائد</span>'}</td><td>${t.members.length ? t.members.join('، ') : '<span class="faint">—</span>'}<span class="sub">${t.members.length} أعضاء</span></td><td class="num">${t.today}</td><td>${t.rate ? `<span class="row" style="gap:4px">${ic('star', 'i-sm')}<span class="num">${t.rate}</span></span>` : '<span class="faint">—</span>'}</td><td>${tg(t.active)}</td><td><button class="btn btn-ghost btn-sm">الأعضاء والقائد</button></td></tr>`).join(''),
  { pager: false });

V.customers = () => `<div class="toolbar">${search('الاسم أو الجوال', 280)}</div>` +
  table(['العميل', 'الجوال', 'الطلبات', 'آخر طلب', 'الشكاوى', 'الحالة', ''],
  [['سارة أحمد', '05x xxx 1234', 7, '4 أكتوبر', 1, 1], ['عبدالله السبيعي', '05x xxx 5502', 3, '3 أكتوبر', 1, 1], ['نورة العتيبي', '05x xxx 4410', 1, '4 أكتوبر', 0, 1], ['فهد المطيري', '05x xxx 6604', 2, '2 أكتوبر', 0, 0]]
  .map(r => `<tr><td><div class="row">${avatar(r[0])}${r[0]}</div></td><td dir="ltr" style="text-align:right" class="num">${r[1]}</td><td class="num">${r[2]}</td><td>${r[3]}</td><td class="num">${r[4]}</td><td>${r[5] ? '<span class="badge badge-confirmed">نشط</span>' : '<span class="badge badge-cancelled">موقوف</span>'}</td><td><button class="btn btn-ghost btn-sm">عرض</button></td></tr>`).join(''), { n: 4, total: 212 });

V.workers = () => `<div class="toolbar">${search('اسم الموظف')}<div class="input input-sm" style="width:160px"><select><option>كل الأنواع</option><option>فريق الزيارات</option><option>خادمة</option></select></div><div class="input input-sm" style="width:150px"><select><option>كل الحالات</option></select></div><span class="grow"></span><button class="btn btn-primary btn-sm">${ic('plus', 'i-sm')} إضافة موظف</button></div>` +
  table(['الموظف', 'النوع', 'الجوال', 'الحالة', 'الفريق / العقد الحالي', 'التقييم', 'المنجزة', ''],
  workers.map(w => { const k = contracts.find(x => x.st === 'active' && x.w === w.n); const t = teams.find(x => x.n === w.team); return `<tr><td><div class="row">${avatar(w.n)}${w.n}</div></td><td>${w.type === 'cleaner' ? '<span class="badge badge-confirmed">فريق الزيارات</span>' : '<span class="badge badge-assigned">خادمة</span>'}</td><td dir="ltr" style="text-align:right" class="num">${w.ph}</td><td>${{ active: '<span class="badge badge-confirmed">نشطة</span>', on_leave: '<span class="badge badge-under_review">إجازة</span>', inactive: '<span class="badge badge-cancelled">غير نشطة</span>' }[w.st]}</td><td>${w.type === 'cleaner' ? `<b>${w.team}</b><span class="sub">${t && t.leader === w.n ? 'قائدة الفريق' : 'عضو'}</span>` : k ? `<a href="#" data-kt="${k.no}" class="num">${k.no}</a><span class="sub">حتى ${k.to}</span>` : '<span class="faint">متاحة لعقد</span>'}</td><td><span class="row" style="gap:4px">${ic('star', 'i-sm')}<span class="num">${w.rate}</span></span></td><td class="num">${w.cnt}</td><td><button class="btn btn-ghost btn-sm">تعديل</button></td></tr>`; }).join(''), { n: workers.length, total: workers.length });

V.services = () => table(['الخدمة', 'الأسعار الفعالة', 'المدة التقريبية', 'الترتيب', 'مفعلة', ''],
  [['home2', 'تنظيف شامل للمنزل', 2, '4–6 ساعات', 1, 1], ['sofa', 'الكنب والسجاد', 2, '2–3 ساعات', 2, 1], ['window', 'النوافذ والواجهات', 1, 'ساعتان', 3, 1], ['building', 'تنظيف المكاتب', 1, 'حسب المساحة', 4, 0]]
  .map(r => `<tr><td><div class="row"><span class="icon-tile" style="width:34px;height:34px">${ic(r[0], 'i-sm')}</span><b>${r[1]}</b></div></td><td class="num">${r[2]}</td><td>${r[3]}</td><td class="num">${r[4]}</td><td>${tg(r[5])}</td><td><button class="btn btn-ghost btn-sm">تعديل</button></td></tr>`).join(''),
  { header: panelH('الخدمات', `<button class="btn btn-primary btn-sm">${ic('plus', 'i-sm')} خدمة جديدة</button>`), pager: false });

V.pricing = () => `<div class="alert alert-info" style="margin-bottom:16px">${ic('alert')}تعديل السعر لا يؤثر على الطلبات السابقة (السعر محفوظ داخل الطلب)، ويُسجل في سجل العمليات.</div>` +
  table(['الخدمة', 'الخيار', 'الوحدة', 'السعر', 'ساري من', 'مفعل', ''],
  [['تنظيف شامل', 'شقة حتى 3 غرف', 'ثابت', 250, '1 سبتمبر 2026', 1], ['تنظيف شامل', 'فيلا / أكثر من 3 غرف', 'ثابت', 400, '1 سبتمبر 2026', 1], ['الكنب والسجاد', 'كنب — للمقعد', 'للقطعة', 25, '1 سبتمبر 2026', 1], ['الكنب والسجاد', 'سجاد — للمتر', 'للمتر', 12, '1 سبتمبر 2026', 1], ['النوافذ', 'حتى 10 نوافذ', 'ثابت', 120, '1 سبتمبر 2026', 1], ['المكاتب', 'بالساعة', 'ساعة', 75, '—', 0]]
  .map(r => `<tr><td>${r[0]}</td><td><b>${r[1]}</b></td><td>${r[2]}</td><td class="num">${money(r[3])}</td><td>${r[4]}</td><td>${tg(r[5])}</td><td><button class="btn btn-ghost btn-sm">تعديل</button></td></tr>`).join(''),
  { header: panelH('قائمة الأسعار', `<button class="btn btn-primary btn-sm">${ic('plus', 'i-sm')} سعر جديد</button>`), pager: false });

V.payments = () => `<div class="alert alert-info" style="margin-bottom:16px">${ic('cash')}لا يوجد دفع داخل التطبيق. يُنشأ الاستحقاق تلقائياً عند إتمام الزيارة أو نهاية كل شهر من العقد، وتُسجل الإدارة التحصيل النقدي.</div>
  <div class="kpis" style="margin-bottom:16px">
    <div class="kpi"><span class="t-sm muted">محصّل اليوم</span><div class="v num">${money(1058)}</div></div>
    <div class="kpi"><span class="t-sm muted">مستحق غير محصّل</span><div class="v num">${money(3087.5)}</div><span class="d">3 استحقاقات</span></div>
    <div class="kpi"><span class="t-sm muted">متأخر أكثر من 3 أيام</span><div class="v num">1</div></div>
    <div class="kpi"><span class="t-sm muted">معفى هذا الشهر</span><div class="v num">${money(0)}</div></div></div>` +
  table(['المرجع', 'العميل', 'النوع', 'الفترة / التاريخ', 'المبلغ', 'الحالة', ''],
  [['CT-2026-000011', 'منى الشهري', 'عقد · الشهر 2', '1–30 أكتوبر', 1000, 'due'], ['BK-2026-000118', 'عبدالله السبيعي', 'زيارة', '3 أكتوبر', 287.5, 'due'], ['CT-2026-000014', 'سارة أحمد', 'عقد · الشهر 1', '10 أكتوبر – 8 نوفمبر', 1800, 'due'], ['BK-2026-000109', 'منى الشهري', 'زيارة', '2 أكتوبر', 345, 'collected'], ['CT-2026-000005', 'خالد الحربي', 'عقد · إنهاء مبكر', '1–18 أغسطس', 1044, 'collected']]
  .map(r => `<tr><td class="num"><b>${r[0]}</b></td><td>${r[1]}</td><td>${r[2]}</td><td>${r[3]}</td><td class="num">${money(r[4])}</td><td>${badge(r[5], PST)}</td><td>${r[5] === 'due' ? '<button class="btn btn-secondary btn-sm">تسجيل التحصيل</button>' : '<span class="t-xs muted">بواسطة مدير العمليات</span>'}</td></tr>`).join(''), { n: 5, total: 318 });

let ctTab = 'contracts';
V.contracts = () => {
  const tabs = `<div class="tabs"><span class="chip ${ctTab === 'contracts' ? 'is-active' : ''}" data-ct="contracts">العقود <span class="n num">${contracts.length}</span></span><span class="chip ${ctTab === 'requests' ? 'is-active' : ''}" data-ct="requests">طلبات الاستبدال والإنهاء <span class="n num">${requests.filter(r => ['open', 'under_review'].includes(r.st)).length}</span></span><span class="chip ${ctTab === 'plans' ? 'is-active' : ''}" data-ct="plans">الباقات</span></div>`;
  if (ctTab === 'requests') return tabs + table(['الطلب', 'النوع', 'العقد', 'العميل', 'العاملة الحالية', 'السبب', 'الحالة', ''],
    requests.map(r => `<tr class="click" data-rq="${r.no}"><td class="num"><b>${r.no}</b><span class="sub">${r.at}</span></td><td>${r.type === 'terminate' ? `<span class="row" style="gap:4px;color:var(--danger-600)">${ic('x', 'i-sm')}${RTYPE[r.type]}</span>` : `<span class="row" style="gap:4px">${ic('swap', 'i-sm')}${RTYPE[r.type]}</span>`}</td><td class="num">${r.ct}</td><td>${r.c}</td><td>${r.w}</td><td>${r.reason}</td><td>${badge(r.st, RST)}</td><td><button class="btn btn-secondary btn-sm">معالجة</button></td></tr>`).join(''), { n: 3, total: 3 });
  if (ctTab === 'plans') return tabs + table(['الباقة', 'أيام العمل', 'ساعات اليوم', 'السعر الشهري', 'المدة المسموحة', 'مفعلة', ''],
    [['دوام كامل', 6, 8, 1800, '1–12 شهراً', 1], ['نصف يوم', 6, 4, 1200, '1–12 شهراً', 1], ['دوام جزئي', 3, 6, 1000, '1–6 أشهر', 1]]
    .map(r => `<tr><td><b>${r[0]}</b></td><td class="num">${r[1]} أيام</td><td class="num">${r[2]} ساعات</td><td class="num">${money(r[3])}</td><td>${r[4]}</td><td>${tg(r[5])}</td><td><button class="btn btn-ghost btn-sm">تعديل</button></td></tr>`).join(''),
    { header: panelH('باقات العقود', `<button class="btn btn-primary btn-sm">${ic('plus', 'i-sm')} باقة جديدة</button>`), pager: false });
  return tabs + `<div class="toolbar">${search('رقم العقد أو العميل')}<div class="input input-sm" style="width:150px"><select><option>كل الحالات</option></select></div></div>` +
    table(['رقم العقد', 'العميل', 'الباقة', 'الفترة', 'العاملة الحالية', 'الحالة', 'الشهري'],
    contracts.map(k => `<tr class="click" data-kt="${k.no}"><td class="num"><b>${k.no}</b></td><td>${k.c}<span class="sub">${k.area}</span></td><td>${k.plan}<span class="sub">${k.m} ${k.m === 1 ? 'شهر' : 'أشهر'}</span></td><td>${k.from} – ${k.to}</td><td>${k.w || '<span class="faint">—</span>'}</td><td>${badge(k.st, KST)}</td><td class="num">${money(k.monthly)}</td></tr>`).join(''), { n: contracts.length, total: contracts.length });
};

V.ratings = () => `<div class="toolbar">${search('رقم الطلب أو الفريق أو الخادمة')}<div class="input input-sm" style="width:150px"><select><option>كل التقييمات</option><option>3 نجوم وأقل</option></select></div></div>` +
  table(['الطلب / العقد', 'العميل', 'الفريق / الخادمة', 'تقييم الخدمة', 'تقييم الفريق / الخادمة', 'التعليق', 'ظاهر'],
  [['BK-2026-000118', 'عبدالله السبيعي', 'فريق ب', 2, 3, 'التنظيف غير مكتمل في المطبخ', 1], ['CT-2026-000007', 'عبدالله السبيعي', 'خديجة', 5, 5, 'عمل ممتاز والتزام بالموعد.', 1], ['BK-2026-000104', 'منى الشهري', 'فريق أ', 4, 5, '—', 1], ['BK-2026-000099', 'خالد الحربي', 'فريق ب', 5, 4, 'تعليق مخفي لاحتوائه على بيانات شخصية', 0]]
  .map(r => `<tr><td class="num"><b>${r[0]}</b></td><td>${r[1]}</td><td>${r[2]}</td><td>${stars(r[3])}</td><td>${stars(r[4])}</td><td class="t-sm" style="white-space:normal;min-width:200px">${r[5]}</td><td>${tg(r[6])}</td></tr>`).join(''), { n: 4, total: 541 });

let cSel = 0;
V.complaints = () => {
  const c = complaints[cSel];
  return `<div class="split">
    <div class="panel"><div class="panel-h"><div class="tabs" style="margin:0"><span class="chip is-active">الكل</span><span class="chip">مفتوحة</span><span class="chip">قيد المراجعة</span></div></div>
      ${complaints.map((x, i) => `<div class="c-item ${i === cSel ? 'on' : ''}" data-c="${i}"><div class="row-between"><b class="t-sm num">${x.no}</b>${badge(x.st, CST)}</div><div class="t-sm">${x.type}</div><div class="t-xs muted">${x.c} · ${x.at}</div></div>`).join('')}
    </div>
    <div class="panel">
      <div class="panel-h"><div class="stack"><h2><span class="num">${c.no}</span> · ${c.type}</h2><span class="t-sm muted">${c.c} · الطلب <a href="#" data-bk="${c.bk}">${c.bk}</a></span></div>
        <div class="row"><div class="input input-sm" style="width:150px"><select>${Object.entries(CST).map(([k, v]) => `<option ${k === c.st ? 'selected' : ''}>${v}</option>`).join('')}</select></div><button class="btn btn-secondary btn-sm">تحديث الحالة</button></div></div>
      <div class="panel-b thread">
        <div class="msg cust">التنظيف لم يكن مكتملاً، المطبخ بقي كما هو تقريباً. مرفق صورتان.<small>${c.c} · منذ ساعة</small>
          <div class="row" style="gap:6px;margin-top:6px;flex-wrap:wrap"><span class="chip" style="height:28px">${ic('clip', 'i-sm')} kitchen-1.jpg</span><span class="chip" style="height:28px">${ic('clip', 'i-sm')} kitchen-2.jpg</span></div></div>
        <div class="msg note"><b class="t-xs">ملاحظة داخلية — لا تظهر للعميل</b><br>تم التواصل مع العاملة، أفادت بضيق الوقت بسبب الطلب التالي.<small>مدير العمليات · منذ 20 دقيقة</small></div>
        <div class="sys">تغيرت الحالة: مفتوحة ← قيد المراجعة · مسجلة في سجل العمليات</div>
        <div class="msg adm">نعتذر عن ذلك. سنرسل عاملة لإكمال تنظيف المطبخ دون تكلفة إضافية. هل يناسبك غداً 10 صباحاً؟<small>خدمة العملاء · منذ 10 دقائق</small></div>
        <div class="field" style="margin-top:8px"><textarea class="input" placeholder="اكتب رداً للعميل…"></textarea></div>
        <div class="row-between"><label class="row t-sm" style="gap:6px">${tg(0)} ملاحظة داخلية</label><div class="row"><button class="btn-icon" style="width:36px;height:36px" aria-label="إرفاق">${ic('clip')}</button><button class="btn btn-primary btn-sm">إرسال الرد</button></div></div>
      </div>
    </div></div>`;
};

V.notifications = () => `<div class="alert alert-info" style="margin-bottom:16px">${ic('alert')}تُرسل الإشعارات تلقائياً عند تغير الحالة. حدّد من يستلم كل حدث.</div>` +
  table(['الحدث', 'العميل', 'قائد الفريق / الخادمة', 'الإدارة', 'القناة'],
  [['تم إنشاء طلب', 0, 0, 1], ['تم تأكيد الطلب', 1, 0, 0], ['تم رفض الطلب', 1, 0, 0], ['تم الإسناد', 1, 1, 0], ['رفضت العاملة الإسناد', 0, 0, 1], ['في الطريق', 1, 0, 0], ['تم الإكمال', 1, 0, 0], ['تم الإلغاء', 1, 1, 1], ['شكوى جديدة', 0, 0, 1], ['رد على شكوى', 1, 0, 0],
   ['طلب عقد جديد', 0, 0, 1], ['تأكيد العقد / إسناد العاملة', 1, 1, 0], ['بدء العقد', 1, 1, 0], ['طلب استبدال أو إنهاء', 0, 0, 1], ['اعتماد / رفض الطلب', 1, 0, 0], ['تغيير العاملة في العقد', 1, 1, 0], ['قرب نهاية العقد (3 أيام)', 1, 0, 1], ['استحقاق دفعة نقدية', 1, 0, 1]]
  .map(r => `<tr><td><b>${r[0]}</b></td>${[1, 2, 3].map(i => `<td>${tg(r[i])}</td>`).join('')}<td class="t-sm muted">Push + داخل التطبيق</td></tr>`).join(''),
  { header: panelH('مصفوفة الإشعارات', '<button class="btn btn-secondary btn-sm">تعديل نصوص الرسائل</button>'), pager: false });

V.reports = () => {
  const days = [['سبت', 11], ['أحد', 14], ['اثنين', 9], ['ثلاثاء', 12], ['أربعاء', 16], ['خميس', 18], ['جمعة', 7]];
  return `<div class="toolbar"><div class="input input-sm" style="width:220px">${ic('calendar', 'i-sm')}<input value="28 سبتمبر – 4 أكتوبر"></div><span class="grow"></span><button class="btn btn-secondary btn-sm">${ic('download', 'i-sm')} تصدير CSV</button></div>
  <div class="kpis">
    <div class="kpi"><span class="t-sm muted">إجمالي الطلبات</span><div class="v num">87</div></div>
    <div class="kpi"><span class="t-sm muted">نسبة الإكمال</span><div class="v num">91%</div></div>
    <div class="kpi"><span class="t-sm muted">متوسط التقييم</span><div class="v num">4.7</div></div>
    <div class="kpi"><span class="t-sm muted">الإيراد</span><div class="v num">${money(24310)}</div></div></div>
  <div class="cols-2">
    <div class="panel">${panelH('الطلبات حسب اليوم')}<div class="panel-b"><div class="chart">${days.map(([d, v]) => `<div class="col"><b class="num">${v}</b><span style="height:${v / 18 * 130}px"></span>${d}</div>`).join('')}</div></div></div>
    ${table(['الخدمة', 'الطلبات', 'الإيراد'], [['تنظيف شامل', 41, 13980], ['الكنب والسجاد', 22, 4930], ['النوافذ', 16, 2208], ['المكاتب', 8, 3192]].map(r => `<tr><td>${r[0]}</td><td class="num">${r[1]}</td><td class="num">${money(r[2])}</td></tr>`).join(''), { header: panelH('الأكثر طلباً'), pager: false })}
  </div>`;
};

const field = (label, value, hint = '') => `<div class="field"><label>${label}</label><div class="input"><input value="${value}" dir="ltr"></div>${hint ? `<span class="hint">${hint}</span>` : ''}</div>`;
const sel = (label, opts, hint = '') => `<div class="field"><label>${label}</label><div class="input"><select>${opts.map(o => `<option>${o}</option>`).join('')}</select></div>${hint ? `<span class="hint">${hint}</span>` : ''}</div>`;
const sw = (title, hint, on) => `<div class="field full row-between" style="flex-direction:row"><div class="stack"><b class="t-sm">${title}</b><span class="hint">${hint}</span></div>${tg(on)}</div>`;

V.settings = () => `
  <div class="panel settings-sec">${panelH('عام')}<div class="panel-b form-grid">
    ${sel('العملة', ['ر.س (مثال)'], 'بانتظار قرار السوق المستهدف')}
    ${sel('المنطقة الزمنية', ['Asia/Riyadh (مثال)'])}
    ${field('نسبة الضريبة %', '15')}
    ${sel('اللغة الافتراضية', ['العربية', 'English (لاحقاً)'])}
  </div></div>
  <div class="panel settings-sec">${panelH('المواعيد')}<div class="panel-b form-grid">
    ${field('بداية ساعات العمل', '08:00')}${field('نهاية ساعات العمل', '20:00')}
    ${field('طول الفترة الزمنية (دقيقة)', '120')}${field('أقل مهلة للحجز قبل الموعد (ساعة)', '12')}
  </div></div>
  <div class="panel settings-sec">${panelH('السياسات')}<div class="panel-b form-grid">
    ${sel('يسمح للعميل بالإلغاء حتى حالة', ['تم الإسناد', 'مؤكد', 'قيد المراجعة'])}
    ${field('مهلة الإلغاء قبل الموعد (ساعة)', '6')}
    ${sw('السماح للعاملة برفض الإسناد', 'عند الرفض يعود الطلب إلى "مؤكد" مع إشعار للإدارة', 1)}
    ${sw('إظهار رقم العميل للعاملة من حالة "في الطريق"', 'خارج هذه الفترة يبقى الرقم مخفياً', 1)}
  </div></div>
  <div class="panel settings-sec">${panelH('العقود والتحصيل')}<div class="panel-b form-grid">
    ${sel('موعد الدفع في العقد', ['نهاية كل شهر', 'نهاية العقد'])}
    ${sel('احتساب الإنهاء المبكر', ['الأيام الفعلية', 'الشهر كاملاً'])}
    ${field('مهلة إرسال العاملة البديلة (يوم)', '2')}
    ${field('الحد الأقصى لطلبات الاستبدال في العقد', '0', '0 = بلا حد، والإدارة تقرر')}
    ${sw('خصم أيام انتظار البديلة من المستحق', 'الأيام التي لا توجد فيها عاملة لا تُحتسب على العميل', 1)}
    ${sw('الدفع داخل التطبيق', 'غير مفعل — الدفع نقداً عند الإتمام. يمكن تفعيله مستقبلاً', 0)}
  </div><div class="panel-h" style="border-top:1px solid var(--n-100);border-bottom:0;justify-content:flex-end"><button class="btn btn-primary btn-sm">حفظ التغييرات</button></div></div>
  ${table(['الوقت', 'المستخدم', 'العملية', 'التفاصيل', 'IP'],
    [['اليوم 8:10 ص', 'مدير العمليات', 'إسناد طلب', 'BK-2026-000125 ← فاطمة', '10.0.4.21'], ['اليوم 7:58 ص', 'خدمة العملاء', 'تغيير حالة شكوى', 'CM-000045: مفتوحة ← قيد المراجعة', '10.0.4.33'], ['أمس 6:12 م', 'المدير العام', 'تعديل سعر', 'شقة حتى 3 غرف: 230 ← 250', '10.0.4.10'], ['أمس 4:40 م', 'مدير العمليات', 'رفض طلب', 'BK-2026-000112 — خارج منطقة الخدمة', '10.0.4.21']]
    .map(r => `<tr><td>${r[0]}</td><td>${r[1]}</td><td><b>${r[2]}</b></td><td class="t-sm">${r[3]}</td><td class="num t-sm muted" dir="ltr" style="text-align:right">${r[4]}</td></tr>`).join(''),
    { header: panelH('سجل العمليات (Audit Log)', '<button class="btn btn-secondary btn-sm">المستخدمون والأدوار</button>'), n: 4, total: 1204 })}`;

/* ============ Booking drawer ============ */
const FLOW = ['pending', 'confirmed', 'assigned', 'on_the_way', 'in_progress', 'completed'];
function openBooking(no) {
  const b = bookings.find(x => x.no === no);
  if (!b) return;
  const idx = FLOW.indexOf(b.st);
  let middle = '', actions = '';
  if (b.st === 'pending') {
    actions = `<button class="btn btn-danger btn-sm" style="flex:1">رفض الطلب</button><button class="btn btn-primary btn-sm" style="flex:2">تأكيد الطلب</button>`;
  } else if (b.st === 'confirmed') {
    // CR-3: الزيارة تُسند لفريق كامل
    middle = `<div><div class="t-sm" style="font-weight:600;margin-bottom:8px">إسناد إلى فريق</div><div class="stack" style="gap:8px">
      ${teams.map(t => `<div class="worker-opt ${t.busy ? 'busy' : ''}" data-w="${t.n}"><span class="radio"></span><span class="icon-tile" style="width:32px;height:32px">${ic('users', 'i-sm')}</span><div class="stack" style="flex:1"><b class="t-sm">${t.n}</b><span class="t-xs muted">${t.busy || `متاح · القائد: ${t.leader} · ${t.members.length} أعضاء`}</span></div>${t.rate ? `<span class="t-xs row" style="gap:3px">${ic('star', 'i-sm')}${t.rate}</span>` : ''}</div>`).join('')}
      <span class="hint">الفريق المشغول أو غير المفعّل يظهر للعلم ولا يمكن اختياره. يُتحقق من التعارض في الخادم أيضاً.</span></div></div>`;
    actions = `<button class="btn btn-ghost btn-sm" style="flex:1" data-close>إلغاء</button><button class="btn btn-primary btn-sm" style="flex:2" id="assignBtn" disabled>اختر فريقاً</button>`;
  } else if (['assigned', 'on_the_way', 'in_progress'].includes(b.st)) {
    actions = `<button class="btn btn-danger btn-sm" style="flex:1">إلغاء الطلب</button>` +
      (b.st === 'assigned' ? '<button class="btn btn-secondary btn-sm" style="flex:1">سحب الإسناد</button>' : '') +
      (b.st === 'in_progress' ? '<button class="btn btn-primary btn-sm" style="flex:1">تعليم كمكتمل</button>' : '');
  }
  const actor = i => i === 0 ? 'العميل' : i >= 3 ? `قائد ${b.w || 'الفريق'}` : 'مدير العمليات';
  const history = idx >= 0
    ? `<div><div class="t-sm" style="font-weight:600;margin-bottom:8px">السجل</div><ul class="timeline t-sm">${FLOW.map((s, i) =>
        `<li class="${i < idx ? 'done' : i === idx ? 'done current' : 'todo'}"${i === FLOW.length - 1 ? ' style="padding-bottom:0"' : ''}>${ST[s]}${i <= idx ? `<div class="t-xs muted">بواسطة ${actor(i)}</div>` : ''}</li>`).join('')}</ul></div>`
    : `<div class="alert ${b.st === 'rejected' ? 'alert-error' : 'alert-warning'}">${ic('alert')}${b.st === 'rejected' ? 'رُفض الطلب: خارج منطقة الخدمة.' : 'أُلغي الطلب من العميل قبل الإسناد.'}</div>`;

  document.getElementById('drawer').innerHTML = `
    <div class="drawer-h"><div class="stack" style="flex:1"><b class="num">${b.no}</b><span class="t-xs muted">أُنشئ السبت 8:12 م</span></div>${badge(b.st)}<button class="btn-icon" data-close aria-label="إغلاق">${ic('x')}</button></div>
    <div class="drawer-b">
      <div class="card"><div class="kv"><span>العميل</span><span>${b.c}</span></div><div class="kv"><span>الجوال</span><span dir="ltr" class="num">${b.ph}</span></div><div class="kv"><span>الخدمة</span><span>${b.svc}</span></div><div class="kv"><span>الموعد</span><span>${b.d} · ${b.t}</span></div><div class="kv"><span>العنوان</span><span>${b.area}، شارع 12، مبنى 8</span></div><div class="kv"><span>الفريق</span><span>${b.w || '—'}</span></div></div>
      <div class="card"><div class="kv"><span>المبلغ</span><b class="num">${money(b.amt)}</b></div><div class="kv"><span>الدفع</span>${payCell(b)}</div></div>
      ${middle}${history}
    </div>
    ${actions ? `<div class="drawer-f">${actions}</div>` : ''}`;
  document.getElementById('drawer').classList.add('open');
  document.getElementById('scrim').classList.add('open');
}
function closeDrawer() {
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('scrim').classList.remove('open');
}

/* ============ Contract drawer ============ */
function showDrawer(html) {
  document.getElementById('drawer').innerHTML = html;
  document.getElementById('drawer').classList.add('open');
  document.getElementById('scrim').classList.add('open');
}
// العقود: الخادمات فقط (CR-3)
const pickList = hint => `<div class="stack" style="gap:8px">${workers.filter(w => w.st === 'active' && w.type === 'housekeeper').map(w => `<div class="worker-opt ${w.busy ? 'busy' : ''}" data-w="${w.n}"><span class="radio"></span><span class="avatar" style="width:32px;height:32px">${w.n[0]}</span><div class="stack" style="flex:1"><b class="t-sm">${w.n}</b><span class="t-xs muted">${w.busy || 'متاحة لكامل فترة العقد'}</span></div><span class="t-xs row" style="gap:3px">${ic('star', 'i-sm')}${w.rate}</span></div>`).join('')}<span class="hint">${hint}</span></div>`;

function openContract(no) {
  const k = contracts.find(x => x.no === no);
  if (!k) return;
  const reqs = requests.filter(r => r.ct === k.no);
  let middle = '', actions = '';
  if (k.st === 'pending') actions = `<button class="btn btn-danger btn-sm" style="flex:1">رفض العقد</button><button class="btn btn-primary btn-sm" style="flex:2">تأكيد العقد</button>`;
  if (k.st === 'confirmed') {
    middle = `<div><div class="t-sm" style="font-weight:600;margin-bottom:8px">إسناد عاملة للعقد</div>${pickList('تظهر فقط العاملات المتاحات لكامل الفترة؛ يُتحقق من التداخل في الخادم.')}</div>`;
    actions = `<button class="btn btn-ghost btn-sm" style="flex:1" data-close>إلغاء</button><button class="btn btn-primary btn-sm" style="flex:2" id="assignBtn" disabled>اختر عاملة</button>`;
  }
  if (k.st === 'active') actions = `<button class="btn btn-danger btn-sm" style="flex:1">إنهاء العقد</button><button class="btn btn-secondary btn-sm" style="flex:1">${ic('swap', 'i-sm')} تغيير العاملة</button>`;
  const hist = k.history ? `<div><div class="t-sm" style="font-weight:600;margin-bottom:8px">سجل العاملات في العقد</div><ul class="timeline t-sm">${k.history.map((h, i) =>
    `<li class="${h[2] ? 'done' : 'done current'}"${i === k.history.length - 1 ? ' style="padding-bottom:0"' : ''}><b>${h[0]}</b><div class="t-xs muted">${h[1]} – ${h[2] || 'حالياً'}${h[3] ? ' · ' + h[3] : ''}</div></li>`).join('')}</ul></div>` : '';
  showDrawer(`
    <div class="drawer-h"><div class="stack" style="flex:1"><b class="num">${k.no}</b><span class="t-xs muted">عقد ${k.plan}</span></div>${badge(k.st, KST)}<button class="btn-icon" data-close aria-label="إغلاق">${ic('x')}</button></div>
    <div class="drawer-b">
      <div class="card"><div class="kv"><span>العميل</span><span>${k.c}</span></div><div class="kv"><span>الجوال</span><span dir="ltr" class="num">${k.ph}</span></div><div class="kv"><span>الباقة</span><span>${k.plan}</span></div><div class="kv"><span>الفترة</span><span>${k.from} – ${k.to} (${k.m} ${k.m === 1 ? 'شهر' : 'أشهر'})</span></div><div class="kv"><span>العنوان</span><span>${k.area}</span></div><div class="kv"><span>العاملة الحالية</span><span>${k.w || '—'}</span></div></div>
      <div class="card"><div class="kv"><span>الشهري</span><b class="num">${money(k.monthly)}</b></div><div class="kv"><span>الإجمالي</span><span class="num">${money(k.monthly * k.m)}</span></div><div class="kv"><span>الدفع</span><span class="t-sm muted">نقداً نهاية كل شهر</span></div></div>
      ${reqs.length ? `<div><div class="t-sm" style="font-weight:600;margin-bottom:8px">طلبات العميل</div>${reqs.map(r => `<div class="card row" style="padding:10px 12px;margin-bottom:6px;cursor:pointer" data-rq="${r.no}"><span class="stack" style="flex:1"><b class="t-sm">${RTYPE[r.type]}</b><span class="t-xs muted">${r.no} · ${r.reason}</span></span>${badge(r.st, RST)}</div>`).join('')}</div>` : ''}
      ${middle}${hist}
    </div>
    ${actions ? `<div class="drawer-f">${actions}</div>` : ''}`);
}

function openRequest(no) {
  const r = requests.find(x => x.no === no);
  if (!r) return;
  const open = ['open', 'under_review'].includes(r.st);
  const body = r.type === 'replace_worker'
    ? (open ? `<div><div class="t-sm" style="font-weight:600;margin-bottom:8px">العاملة البديلة</div>${pickList('عند الاعتماد: يُغلق إسناد ' + r.w + ' ويبدأ إسناد البديلة، ويبقى العقد سارياً.')}</div>
        <div class="field"><label>تاريخ بدء البديلة</label><div class="input"><input value="الأحد 18 أكتوبر 2026"></div></div>` : '')
    : `<div class="card card-flat"><div class="kv"><span>تاريخ الإنهاء المطلوب</span><span>31 أكتوبر</span></div><div class="kv"><span>المستحق حتى الإنهاء</span><b class="num">${money(1000)}</b></div><span class="t-xs faint">محسوب بالأيام الفعلية حسب الإعدادات</span></div>`;
  showDrawer(`
    <div class="drawer-h"><div class="stack" style="flex:1"><b class="num">${r.no} · ${RTYPE[r.type]}</b><span class="t-xs muted">${r.c} · العقد <a href="#" data-kt="${r.ct}">${r.ct}</a></span></div>${badge(r.st, RST)}<button class="btn-icon" data-close aria-label="إغلاق">${ic('x')}</button></div>
    <div class="drawer-b">
      <div class="card"><div class="kv"><span>السبب</span><b>${r.reason}</b></div><div class="kv"><span>العاملة الحالية</span><span>${r.w}</span></div><p class="t-sm" style="margin-top:6px">${r.details}</p></div>
      ${body}
      ${open ? `<div class="field"><label>رد للعميل</label><textarea class="input" placeholder="يظهر للعميل في التطبيق"></textarea></div>` : `<div class="alert alert-success">${ic('check')}تم الاعتماد وتعيين عائشة بدلاً من فاطمة ابتداءً من 14 أكتوبر.</div>`}
    </div>
    ${open ? `<div class="drawer-f"><button class="btn btn-danger btn-sm" style="flex:1">رفض الطلب</button><button class="btn btn-primary btn-sm" style="flex:2" ${r.type === 'replace_worker' ? 'id="assignBtn" disabled' : ''}>${r.type === 'replace_worker' ? 'اختر عاملة بديلة' : 'اعتماد الإنهاء'}</button></div>` : ''}`);
}

/* ============ Router ============ */
function go(v) {
  renderNav(v);
  document.getElementById('title').textContent = NAV.find(n => n[0] === v)[1];
  document.getElementById('view').innerHTML = V[v]();
  document.getElementById('side').classList.remove('open');
  try { history.replaceState(null, '', '#' + v); } catch (e) { /* file:// في بعض المتصفحات */ }
  window.scrollTo(0, 0);
}
document.addEventListener('click', e => {
  const t = e.target;
  const nav = t.closest('[data-v]'); if (nav) return go(nav.dataset.v);
  const g = t.closest('[data-go]'); if (g) { e.preventDefault(); return go(g.dataset.go); }
  const f = t.closest('[data-f]'); if (f) { bkFilter = f.dataset.f; return go('bookings'); }
  const c = t.closest('[data-c]'); if (c) { cSel = +c.dataset.c; return go('complaints'); }
  const ct = t.closest('[data-ct]'); if (ct) { ctTab = ct.dataset.ct; return go('contracts'); }
  const kt = t.closest('[data-kt]'); if (kt) { e.preventDefault(); return openContract(kt.dataset.kt); }
  const rq = t.closest('[data-rq]'); if (rq) { e.preventDefault(); return openRequest(rq.dataset.rq); }
  const bk = t.closest('[data-bk]'); if (bk) { e.preventDefault(); return openBooking(bk.dataset.bk); }
  const w = t.closest('.worker-opt:not(.busy)');
  if (w) {
    document.querySelectorAll('.worker-opt').forEach(x => x.classList.remove('sel'));
    w.classList.add('sel');
    const btn = document.getElementById('assignBtn');
    btn.disabled = false; btn.textContent = 'إسناد إلى ' + w.dataset.w;
    return;
  }
  if (t.closest('[data-close]') || t.id === 'scrim') return closeDrawer();
  if (t.closest('#menuBtn')) return document.getElementById('side').classList.toggle('open');
  const sw = t.closest('.toggle'); if (sw) { sw.classList.toggle('on'); sw.setAttribute('aria-checked', sw.classList.contains('on')); }
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
go(V[location.hash.slice(1)] ? location.hash.slice(1) : 'dashboard');
