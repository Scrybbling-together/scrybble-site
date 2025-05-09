// Array of YouTube links to test
const youtubeLinks = [
  "PL-ntK3MOBOs5vgpZodnaxy-Ti0BYvW65w",
  "PL-ntK3MOBOs4jjwfUk-NZxjt9V-gu9Xv0",
  "PLw9LHJQG82HlSvE1IXMzR_ykDvvgWHMii",
  "PL-4U2d6ASRHn-h6UdaKkKnaAhg2T2kxAj",
  "PLuDCBN4mO8msth5CD8F2yhLgMcWO2gzCM",
  "PL-ntK3MOBOs49foA-Y8GRB4MdfX26e5H3",
  "PL-ntK3MOBOs7XQuEqjcDKvuG_zBIdYYHZ"
];

// Function to send a POST request for a single link
async function testLink(link) {
  const startTime = new Date().getTime();
  const endpoint = `https://smgmusicdisplay.com/youtube-randomiser/playlist/${encodeURIComponent(link)}`;

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        // Add any additional headers if needed
      },
      // If you need to send a body with the POST request, add it here
      // body: JSON.stringify({ /* your data here */ }),
    });

    const endTime = new Date().getTime();
    const responseTime = endTime - startTime;

    console.log(`POST request for ${link.substring(0, 30)}...`);
    console.log(`Status: ${response.status}`);
    console.log(`Response time: ${responseTime}ms`);

    return {
      link,
      status: response.status,
      responseTime,
      success: response.ok
    };
  } catch (error) {
    console.error(`Error for ${link}: ${error.message}`);
    return {
      link,
      status: 'Error',
      responseTime: new Date().getTime() - startTime,
      success: false,
      error: error.message
    };
  }
}

// Function to run all tests in parallel
async function runStressTest() {
  console.log(`Starting stress test with ${youtubeLinks.length} parallel POST requests...`);
  const startTime = new Date().getTime();

  try {
    const results = await Promise.all(youtubeLinks.map(link => testLink(link)));

    const endTime = new Date().getTime();
    const totalTime = endTime - startTime;

    // Display summary
    console.log("\n========== STRESS TEST RESULTS ==========");
    console.log(`Total time: ${totalTime}ms`);
    console.log(`Successful requests: ${results.filter(r => r.success).length}/${results.length}`);

    const avgTime = results.reduce((sum, r) => sum + r.responseTime, 0) / results.length;
    console.log(`Average response time: ${avgTime.toFixed(2)}ms`);

    // Sort by response time to see slowest and fastest
    const sortedResults = [...results].sort((a, b) => b.responseTime - a.responseTime);
    console.log(`Slowest: ${sortedResults[0].responseTime}ms for ${sortedResults[0].link.substring(0, 30)}...`);
    console.log(`Fastest: ${sortedResults[sortedResults.length-1].responseTime}ms for ${sortedResults[sortedResults.length-1].link.substring(0, 30)}...`);

    return results;
  } catch (error) {
    console.error("Error running stress test:", error);
  }
}

// Execute the stress test
runStressTest();
