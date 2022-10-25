function fcalc() {
  var fCurrentYearAMI = drupalSettings.sfgov.mohcd.calculator.currentYearAMI; // this value is coming from configuration in sfgov_pages module
  var fPurchaseYearAMI;
  var fBMRValuation;
  var fPurchaseYearSelect = document.getElementById("purchaseYear");
  var fPurchasePrice = document.getElementById("purchasePrice").value;
  var fPurchaseYearAMI = parseFloat(fPurchaseYearSelect.value);
  var fPurchaseYear = parseInt(fPurchaseYearSelect.options[fPurchaseYearSelect.selectedIndex].text);

  var fFieldError = new Boolean();
  var fFieldErrorArray = new Array();

  //Clear previous error messages
  document.getElementById("purchasePriceError").setAttribute("style", "display: none !important");
  document.getElementById("purchaseYearError").setAttribute("style", "display: none !important");
  document.getElementById("valuationError").setAttribute("style", "display: none !important");

  //Check that the value of purchase price textbox is a number and if so, convert it to float
  if ((isNaN(fPurchasePrice)) || (fPurchasePrice == "") || (fPurchasePrice = null)) {
    document.getElementById("purchasePriceError").setAttribute("style", "display: inline !important");
    fPurchasePrice = 0;
    fFieldErrorArray[0] = 1;
  } else {
    fPurchasePrice = parseFloat(document.getElementById("purchasePrice").value);
    fFieldErrorArray[0] = 0;
  }

  //Check that Purchase Year is selected
  if (isNaN(fPurchaseYear)) {
    document.getElementById("purchaseYearError").setAttribute("style", "display: inline !important");
    fPurchaseYear = 0;
    fFieldErrorArray[1] = 1;
  } else {
    fPurchaseYear = parseInt(document.getElementById("purchaseYear").value);
    fFieldErrorArray[1] = 0;
  }

  //Calculate BMR Valuation
  fBMRValuation = (fPurchasePrice + (fPurchasePrice * ((fCurrentYearAMI - fPurchaseYearAMI) / fPurchaseYearAMI)));

  //Check each field for an error, and if there is one, set fFieldError to true
  fFieldError = 0;
  for (var a = 0; a < fFieldErrorArray.length; a++) {
    if (fFieldErrorArray[a] == 1) {
      fFieldError = 1;
    }
  }

  //Show the result in the "Current BMR Valuation" textbox
  if ((isNaN(fBMRValuation)) || (fFieldError == 1)) {
    //Show error message if any fields have invalid values
    document.getElementById("bmrValuation").value = "n/a";
    document.getElementById("valuationError").setAttribute("style", "display: inline !important");
  } else {
    //Show BMR Valuation results (rounded and no decimals)
    document.getElementById("bmrValuation").value = Math.round(fBMRValuation);
  }
}

document.getElementById("btnCalc").addEventListener("click", (event) => {
  event.preventDefault();
  fcalc();
})
