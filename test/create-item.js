var
  webdriver = require('selenium-webdriver');

"use strict";
describe("Selenium Test", function () {
  it("should perform a Selenium test",  function (done) {
    this.timeout(5000);

     it('performs as expected', function (done) {
         var searchBox;
         var browser = this.browser;
         browser.get('http://google.com')
         .elementByName('q').type('webdriver')
         .elementByName('q').getAttribute('value')
         .then(function(val){
             assert.equal(val, 'webdriver');
         }).then(done, done);
     });
//			driver.Open("/pos/index.php/login");
//			driver.Type("name=username", "admin");
//			driver.Type("name=password", "pointofsale");
//			driver.Click("name=loginButton");
//			driver.WaitForPageToLoad("30000");
//			driver.Click("xpath=(//img[@alt='Menubar Image'])[2]");
//			driver.WaitForPageToLoad("30000");
//			driver.Click("css=div.big_button");
//			driver.Type("id=name", "anItem");
//			driver.Type("id=category", "aCategory");
//			driver.Type("id=cost_price", "21");
//			driver.Type("id=unit_price", "24");
//			driver.Type("id=1_quantity", "2");
//			driver.Type("id=reorder_level", "0");
//			driver.Click("id=submit_form");
//			driver.Click("link=edit");
//			driver.Click("id=TB_closeWindowButton");
//			driver.Click("link=inv");
//			driver.Type("id=newquantity", "2");
//			driver.Type("id=trans_comment", "aaa");
//			driver.Click("id=submit");
//			driver.Click("link=details");
  });


  after(function (done) {
    driver.quit();
    driver.wait(function () {
      done();
    }, 5000);
  });
});
