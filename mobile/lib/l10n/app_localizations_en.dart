// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appName => 'Lam\'a';

  @override
  String get tagline => 'Cleaning you can trust';

  @override
  String get loading => 'Loading…';

  @override
  String get retry => 'Try again';

  @override
  String get back => 'Cancel';

  @override
  String get confirm => 'Confirm';

  @override
  String get send => 'Send';

  @override
  String get save => 'Save';

  @override
  String get saved => 'Saved';

  @override
  String get delete => 'Delete';

  @override
  String get deleted => 'Deleted';

  @override
  String get now => 'now';

  @override
  String get today => 'Today';

  @override
  String get tomorrow => 'Tomorrow';

  @override
  String get from => 'From';

  @override
  String get to => 'To';

  @override
  String get number => 'Number';

  @override
  String get name => 'Name';

  @override
  String get phone => 'Mobile number';

  @override
  String get password => 'Password';

  @override
  String get passwordHint => 'At least 8 characters';

  @override
  String get emailOptional => 'Email (optional)';

  @override
  String money(String amount) {
    return 'SAR $amount';
  }

  @override
  String buildingNo(String n) {
    return 'Bldg $n';
  }

  @override
  String get errNetwork =>
      'Could not reach the server — check your connection and try again.';

  @override
  String errUnexpected(int code) {
    return 'Unexpected error ($code).';
  }

  @override
  String get errSession => 'Could not restore your session.';

  @override
  String errGeneric(String error) {
    return 'Something went wrong: $error';
  }

  @override
  String get errLoad => 'Could not load';

  @override
  String get errPick => 'Could not pick the image';

  @override
  String get loginTitle => 'Sign in';

  @override
  String get loginSubtitle => 'For customers, cleaning teams and housekeepers';

  @override
  String get login => 'Sign in';

  @override
  String get newCustomer => 'New customer? Create an account';

  @override
  String get staffAccountsNote =>
      'Team and housekeeper accounts are created by the agency.';

  @override
  String get registerTitle => 'New account';

  @override
  String get createAccount => 'Create account';

  @override
  String get language => 'Language';

  @override
  String get tabHome => 'Home';

  @override
  String get tabVisits => 'My visits';

  @override
  String get tabContracts => 'My contracts';

  @override
  String get tabAccount => 'Account';

  @override
  String hello(String name) {
    return 'Hi $name 👋';
  }

  @override
  String get heroTitle => 'Monthly housekeeper contract';

  @override
  String get heroSubtitle =>
      'Replace the housekeeper or end the contract right from the app.';

  @override
  String get visitTitle => 'Cleaning visit';

  @override
  String get visitSubtitle =>
      'A professional team arrives on time, cleans and leaves — pay cash when done.';

  @override
  String get noServices => 'No services available right now';

  @override
  String get startsFrom => 'From';

  @override
  String get stepOption => '1. Choose an option';

  @override
  String quantity(String unit) {
    return 'Quantity ($unit)';
  }

  @override
  String get stepAddress => '2. Address';

  @override
  String get stepDateTime => '3. Day and time';

  @override
  String get pickDayFirst => 'Pick a day to see available times.';

  @override
  String get noSlots => 'No times available on this day.';

  @override
  String get notesForTeam => 'Notes for the team (optional)';

  @override
  String get notesForTeamHint => 'e.g. there is a cat at home';

  @override
  String get summary => 'Summary';

  @override
  String get subtotal => 'Subtotal';

  @override
  String get tax => 'VAT';

  @override
  String get total => 'Total';

  @override
  String get cashToLeader =>
      'Pay cash to the team leader once the job is done.';

  @override
  String get confirmBooking => 'Confirm booking';

  @override
  String get myAddresses => 'My addresses';

  @override
  String get newAddress => 'New address';

  @override
  String get noAddresses => 'No addresses yet';

  @override
  String get deleteAddress => 'Delete address';

  @override
  String deleteAddressQ(String label) {
    return 'Delete \"$label\"?';
  }

  @override
  String get addAddressFirst => 'Add an address to continue.';

  @override
  String get addrLabel => 'Address name (e.g. Home)';

  @override
  String get addrCity => 'City';

  @override
  String get addrDistrict => 'District';

  @override
  String get addrStreet => 'Street (optional)';

  @override
  String get addrBuilding => 'Building no. (optional)';

  @override
  String get addrFloor => 'Floor (optional)';

  @override
  String get addrDetails => 'Extra directions (optional)';

  @override
  String get defaultCity => 'Riyadh';

  @override
  String get defaultAddress => 'Default address';

  @override
  String get saveAddress => 'Save address';

  @override
  String get noVisits => 'No visits yet — book your first one from Home';

  @override
  String get visitDetails => 'Visit details';

  @override
  String get bookingReceived =>
      'Booking received! We will confirm it and assign a team shortly.';

  @override
  String get appointment => 'Appointment';

  @override
  String get address => 'Address';

  @override
  String get assignedTeam => 'Assigned team';

  @override
  String get notAssignedYet => 'Not assigned yet';

  @override
  String get yourNotes => 'Your notes';

  @override
  String get cancelReason => 'Cancellation reason';

  @override
  String get serviceAndAmount => 'Service and amount';

  @override
  String get paymentMethod => 'Payment method';

  @override
  String get cashOnCompletion => 'Cash on completion';

  @override
  String get paymentStatus => 'Payment status';

  @override
  String get tracking => 'Tracking';

  @override
  String get yourRating => 'Your rating';

  @override
  String get service => 'Service';

  @override
  String get team => 'Team';

  @override
  String get rateTeam => 'Rate the team';

  @override
  String get rateVisit => 'Rate this visit';

  @override
  String get rateService => 'Rate the service';

  @override
  String get commentOptional => 'Comment (optional)';

  @override
  String get sendRating => 'Submit rating';

  @override
  String get thanksRating => 'Thanks for your rating!';

  @override
  String get cancelVisit => 'Cancel visit';

  @override
  String get cancelReasonOptional => 'Reason (optional)';

  @override
  String get visitCancelled => 'Visit cancelled';

  @override
  String get haveProblem => 'Something wrong? File a complaint';

  @override
  String get hireTitle => 'Hire a housekeeper';

  @override
  String get hireIntro =>
      'Choose a plan. You can request a replacement or end the contract from the app at any time.';

  @override
  String get noPlans => 'No plans available right now';

  @override
  String perMonth(String price) {
    return '$price / month';
  }

  @override
  String daysPerWeek(int n) {
    return '$n days/week';
  }

  @override
  String hoursPerDay(int n) {
    return '$n hours/day';
  }

  @override
  String get contractRequest => 'Contract request';

  @override
  String get durationAndStart => 'Duration and start';

  @override
  String get contractDuration => 'Contract length';

  @override
  String months(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n months',
      one: '1 month',
    );
    return '$_temp0';
  }

  @override
  String startDate(String date) {
    return 'Start date: $date';
  }

  @override
  String get workAddress => 'Work address';

  @override
  String get notesOptional => 'Notes (optional)';

  @override
  String get contractNotesHint => 'e.g. Arabic speaker preferred';

  @override
  String get monthly => 'Monthly';

  @override
  String get payment => 'Payment';

  @override
  String get cashMonthly => 'Cash — paid monthly';

  @override
  String acceptTerms(String version) {
    return 'I accept the contract terms (version $version)';
  }

  @override
  String get sendContractRequest => 'Send contract request';

  @override
  String get newContract => 'New contract';

  @override
  String get noContracts => 'No contracts yet — hire a housekeeper monthly';

  @override
  String dayOf(int day, int total) {
    return 'Day $day of $total';
  }

  @override
  String get contractDetails => 'Contract details';

  @override
  String get contractReceived =>
      'Contract request sent. We will review it and assign a housekeeper.';

  @override
  String get plan => 'Plan';

  @override
  String get schedule => 'Schedule';

  @override
  String scheduleValue(String days, String hours) {
    return '$days days × $hours hours';
  }

  @override
  String get terminatedOn => 'Ended on';

  @override
  String get housekeeper => 'Housekeeper';

  @override
  String get noWorkerYet => 'No housekeeper assigned yet.';

  @override
  String since(String date) {
    return 'Since $date';
  }

  @override
  String get amountsCash => 'Amounts (cash)';

  @override
  String dueOn(String date) {
    return 'Due $date';
  }

  @override
  String get changeRequests => 'Replacement and termination requests';

  @override
  String adminReply(String reply) {
    return 'Agency reply: $reply';
  }

  @override
  String get requestReplace => 'Request a replacement';

  @override
  String get requestTerminate => 'Request to end contract';

  @override
  String get requestPending => 'You have a request under review.';

  @override
  String get cancelContract => 'Cancel contract';

  @override
  String get cancelContractQ =>
      'The contract will be cancelled before it starts. Are you sure?';

  @override
  String get contractCancelled => 'Contract cancelled';

  @override
  String get cancelBeforeStart => 'Cancel before start';

  @override
  String rateName(String name) {
    return 'Rate $name';
  }

  @override
  String get fileComplaint => 'File a complaint';

  @override
  String get reasonDelay => 'Frequent delays';

  @override
  String get reasonQuality => 'Work quality';

  @override
  String get reasonAbsence => 'Absence';

  @override
  String get reasonBehavior => 'Behaviour';

  @override
  String get reasonNotNeeded => 'No longer needed';

  @override
  String get reasonOther => 'Other';

  @override
  String get reason => 'Reason';

  @override
  String get detailsOptional => 'Details (optional)';

  @override
  String get terminateNote =>
      'We review the request; you pay up to the last day actually worked.';

  @override
  String get replaceNote =>
      'We review the request and assign a replacement; waiting days are not charged.';

  @override
  String requestedEndDate(String date) {
    return 'Requested end date: $date';
  }

  @override
  String get sendRequest => 'Send request';

  @override
  String get requestSent => 'Your request was sent';

  @override
  String attachmentsTitle(int count, int max) {
    return 'Photos ($count/$max)';
  }

  @override
  String get gallery => 'Gallery';

  @override
  String get camera => 'Camera';

  @override
  String filesCount(int n) {
    String _temp0 = intl.Intl.pluralLogic(
      n,
      locale: localeName,
      other: '$n attachments',
      one: '1 attachment',
    );
    return '$_temp0';
  }

  @override
  String get cLate => 'Late arrival';

  @override
  String get cQuality => 'Service quality';

  @override
  String get cBehavior => 'Behaviour';

  @override
  String get cPayment => 'Payment issue';

  @override
  String get cOther => 'Other';

  @override
  String get myComplaints => 'My complaints';

  @override
  String get noComplaints =>
      'No complaints. You can file one from a visit or contract page.';

  @override
  String get complaint => 'Complaint';

  @override
  String get regarding => 'Regarding';

  @override
  String regardingX(String number) {
    return 'Regarding: $number';
  }

  @override
  String get complaintClosed => 'This complaint is closed.';

  @override
  String get writeReply => 'Write a reply…';

  @override
  String statusChangedTo(String status) {
    return 'Status changed to \"$status\"';
  }

  @override
  String get complaintSent => 'Complaint sent — we will get back to you soon';

  @override
  String get newComplaint => 'New complaint';

  @override
  String get problemType => 'Problem type';

  @override
  String get describeProblem => 'Describe the problem';

  @override
  String get sendComplaint => 'Send complaint';

  @override
  String get notifications => 'Notifications';

  @override
  String get readAll => 'Mark all read';

  @override
  String get noNotifications => 'No notifications';

  @override
  String get notifyOrders => 'Booking and contract alerts';

  @override
  String get notifyComplaints => 'Complaint alerts';

  @override
  String get editProfile => 'Edit profile';

  @override
  String get phoneLocked => 'Contact the agency to change your number';

  @override
  String get changePassword => 'Change password';

  @override
  String get currentPassword => 'Current password';

  @override
  String get newPassword => 'New password';

  @override
  String get confirmPassword => 'Confirm password';

  @override
  String get passwordsDontMatch => 'Passwords do not match';

  @override
  String get passwordChanged => 'Password changed';

  @override
  String get otherDevicesLoggedOut =>
      'You will be signed out on other devices.';

  @override
  String get logout => 'Sign out';

  @override
  String get logoutQ => 'Sign out of this device?';

  @override
  String get logoutShort => 'Sign out';

  @override
  String get scopeNew => 'New';

  @override
  String get scopeUpcoming => 'Upcoming';

  @override
  String get scopeDone => 'Done';

  @override
  String get myTeam => 'My team';

  @override
  String get youAreLeader => 'You are the team leader';

  @override
  String get memberNote => 'Member — the leader updates status';

  @override
  String get noTeam => 'You are not in a team yet. Contact the agency.';

  @override
  String get noVisitsHere => 'No visits here';

  @override
  String get awaitingYourAcceptance => 'Awaiting your acceptance';

  @override
  String get myRatings => 'My ratings';

  @override
  String get assignment => 'Assignment';

  @override
  String get customerAndLocation => 'Customer and location';

  @override
  String get customer => 'Customer';

  @override
  String get description => 'Directions';

  @override
  String get customerNotes => 'Customer notes';

  @override
  String callCustomer(String phone) {
    return 'Call customer  $phone';
  }

  @override
  String get amountToCollect => 'Cash to collect on completion';

  @override
  String get leaderOnlyNote =>
      'Only the team leader accepts the visit and updates its status.';

  @override
  String get acceptVisit => 'Accept visit';

  @override
  String get visitAccepted => 'Visit accepted';

  @override
  String get reject => 'Reject';

  @override
  String get rejectAssignment => 'Reject assignment';

  @override
  String get rejectReason => 'Reason for rejecting';

  @override
  String get rejectedBack => 'Rejected — returned to the agency';

  @override
  String get completeVisit => 'Complete visit';

  @override
  String completeVisitQ(String amount) {
    return 'Did you receive $amount in cash from the customer?';
  }

  @override
  String get yesReceived => 'Yes, received';

  @override
  String updatedTo(String status) {
    return 'Updated: $status';
  }

  @override
  String get noAssignedContracts => 'No contracts assigned to you';

  @override
  String get currentContract => 'Current contract';

  @override
  String get otherContracts => 'Past and upcoming contracts';

  @override
  String get contract => 'Contract';

  @override
  String get myPeriod => 'My period';

  @override
  String get contactDuringPeriodOnly =>
      'Contact details and address are shown during your work period only.';

  @override
  String averageOf(int n) {
    return 'Average of $n ratings';
  }

  @override
  String get noRatings => 'No ratings yet';
}
