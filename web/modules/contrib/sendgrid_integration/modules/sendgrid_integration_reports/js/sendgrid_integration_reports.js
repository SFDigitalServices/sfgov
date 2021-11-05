/**
 * @file
 * Sendgrid Reports JS for creating graphs using Google Chart JS API.
 */

(function ($) {
  Drupal.behaviors.sendgrid_integration_reports = {
    attach: function (context, settings) {
      google.load("visualization", "1", {
        packages: ["corechart"],
        "callback": drawCharts
      });

      function drawCharts() {
        const dataTableVol = new google.visualization.DataTable();
        dataTableVol.addColumn('datetime', Drupal.t('Date'));
        dataTableVol.addColumn('number', Drupal.t('Opens'));
        dataTableVol.addColumn('number', Drupal.t('Clicks'));
        dataTableVol.addColumn('number', Drupal.t('Delivered'));

        const dataTableSpa = new google.visualization.DataTable();
        dataTableSpa.addColumn('datetime', Drupal.t('Date'));
        dataTableSpa.addColumn('number', Drupal.t('Spam'));
        dataTableSpa.addColumn('number', Drupal.t('Spam Drops'));

        for (let key in settings.sendgrid_integration_reports.global) {
          dataTableVol.addRow([
            new Date(settings.sendgrid_integration_reports.global[key]['date']),
            settings.sendgrid_integration_reports.global[key]['opens'],
            settings.sendgrid_integration_reports.global[key]['clicks'],
            settings.sendgrid_integration_reports.global[key]['delivered']
          ]);
          dataTableSpa.addRow([
            new Date(settings.sendgrid_integration_reports.global[key]['date']),
            settings.sendgrid_integration_reports.global[key]['spam_reports'],
            settings.sendgrid_integration_reports.global[key]['spam_report_drops']
          ]);
        }

        const options = {
          pointSize: 5,
          hAxis: {format: 'MM/dd/yyyy'},
          legend: {position: 'bottom'},
          vAxis: {viewWindowMode: "explicit", viewWindow: {min: 0}}
        };

        const chart0 = new google.visualization.LineChart(document.getElementById('sendgrid-global-volume-chart'));
        chart0.draw(dataTableVol, options);

        const chart1 = new google.visualization.LineChart(document.getElementById('sendgrid-global-spam-chart'));
        chart1.draw(dataTableSpa, options);
      }
    }
  }
})(jQuery);
