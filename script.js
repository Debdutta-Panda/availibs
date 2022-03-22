Array.prototype.isEmpty = function(){ 
    return this.length===0;
}
Array.prototype.peek = function(){ 
    if(this.isEmpty()){
      return null;
    }
    else{
      return this[this.length-1];
    }
}
Array.prototype.addAll = function(newItems){ 
    if(Array.isArray(newItems)){
      newItems.forEach((item,index)=>{
        this.push(item);
      });
    }
}
function leftPad(number, targetLength) {
    var output = number + '';
    while (output.length < targetLength) {
        output = '0' + output;
    }
    return output;
}

class HDate{
    constructor(input){
        this.date = this.todayMillis();
        if(typeof input === 'string'){
            var parts = input.match(/(\d{4})\/(\d{1,2})\/(\d{1,2})/);
            if(Array.isArray(parts)&&parts.length===4){
                var y = parseInt(parts[1]);
                var m = parseInt(parts[2])-1;
                var d = parseInt(parts[3]);
                this.date = new Date(y,m,d).getTime()
            }
        }else if(typeof input === 'number'){
            this.date = input;
        }
    }
    get formatted(){
        var dd = new Date(this.date);
        var y = dd.getFullYear()
        var m = dd.getMonth()+1
        var d = dd.getDate()
        return `${y}/${leftPad(m,2)}/${leftPad(d,2)}`
    }
    todayMillis(){
        return new Date().getTime()
    }
    clone(){
        return new HDate(this.date)
    }
    static DAY_VALUE = 86400000;
    equals(other){
        return (
            other instanceof HDate
            &&this.date===other.date)
    }
}
class HDateRange{
    constructor(input1,input2){
        this.start = new HDate()
        this.end = new HDate()
        if(typeof input1==='string'){
            var parts = input1.split("-");
            if(parts.length>0){
                var s = parts[0]
                var e = parts[parts.length - 1]
                this.start = new HDate(s)
                this.end = new HDate(e)
            }
        }
        if(typeof input2 === "string"){
            this.end = new HDate(input2)
        }
        if(typeof input1 === 'number'){
            this.start = new HDate(input1)
            this.end = this.start.clone()
        }
        if(typeof input2 === 'number'){
            this.end = new HDate(input2)
        }
        if(input1 instanceof HDate){
            this.start = input1.clone()
            this.end = this.start.clone
        }
        if(input2 instanceof HDate){
            this.end = input2.clone()
        }
        if(input1 instanceof HDateRange){
            this.start = input1.start.clone()
            this.end = input1.end.clone()
        }
        if(this.end<this.start){
            var temp = this.start
            this.start = this.end
            this.end = temp
        }
    }
    get formatted(){
        return this.start.formatted + " to " + this.end.formatted
    }
    
    clone(){
        return new HDateRange(this.start.clone(),this.end.clone())
    }
    
    isMergable(other){
        if(other instanceof HDateRange){
            var os = Math.max(this.start.date,other.start.date)
            var oe = Math.min(this.end.date,other.end.date)
            return oe>=os || os-oe===HDate.DAY_VALUE
        }
        return false
    }
    isOverlapping(other){
        if(other instanceof HDateRange){
            var os = Math.max(this.start.date,other.start.date)
            var oe = Math.min(this.end.date,other.end.date)
            return oe>=os
        }
        return false
    }
    static merge(first,second){
        if(first instanceof HDateRange && second instanceof HDateRange){
            var os = Math.max(first.start.date,second.start.date)
            var oe = Math.min(first.end.date,second.end.date)
            if(oe>=os || os-oe===HDate.DAY_VALUE){
                var s = Math.min(first.start.date,second.start.date)
                var e = Math.max(first.end.date,second.end.date)
                return new HDateRange(s,e)
            }
        }
        return null
    }
    static intersect(first,second){
        if(first instanceof HDateRange && second instanceof HDateRange){
            var os = Math.max(first.start.date,second.start.date)
            var oe = Math.min(first.end.date,second.end.date)
            if(oe>=os){
                return new HDateRange(os,oe)
            }
        }
        return null
    }
    equals(other){
        return (other instanceof HDateRange 
        &&this.start.equals(other.start)
        &&this.end.equals(other.end)
            )
    }
    includes(other){
        var i = HDateRange.intersect(this,other)
        return other.equals(i)
    }
    forEachDate(callback){
        if(typeof callback==="function"){
            var s = this.start.date
            var e = this.end.date
            var j = -1
            for(var i=s;i<=e;i=i+HDate.DAY_VALUE){
                var d = new HDate(i)
                callback(d,++j)
            }
        }
    }
}
class HDateRanges{
    constructor(...items){
        this.items = []
        items.forEach((item,index)=>{
            if(item instanceof HDateRange){
                this.items.push(item)
            }
        })
    }
    forEach(callback){
        this.items.forEach((item,index)=>{
            if(typeof callback==='function'){
                callback(item,index)
            }
        })
    }
    peek(){
        if(this.items.length>0){
            return this.items[this.items.length - 1]
        }
        else{
            return null
        }
    }
    isEmpty(){
        return this.items.length===0
    }
    get length(){
        return this.items.length
    }
    push(item){
        if(item instanceof HDateRange){
            this.items.push(item)
        }
    }
    clone(){
        var ret = []
        this.items.forEach((item,index)=>{
            ret.push(item.clone())
        })
        var r = new HDateRanges()
        r.items = ret
        return r
    }
    sorted(){
        return this.clone().sort()
    }
    sort(){
        this.items.sort(function(a,b){
            return a.start.date - b.start.date
        })
        return this
    }
    merge(){
        var ret = null
        this.sort().forEach((item,index)=>{
            if(index===0){
                ret = item
            }
            else{
                ret = HDateRange.merge(ret,item)
            }
        })
        return ret
    }
    merged(){
        return  this.clone().merge()
    }
    optimize(){
        var stack = []
        this.sort().forEach((item,index)=>{
            if(stack.isEmpty() || item.start.date > stack.peek().end.date){
                var t = item.clone()
                stack.push(t)
            }
            if(stack.peek().end.date < item.end.date){
                stack.peek().end = item.end.clone()
            }
        })
        this.items = stack
        var opt = []
        this.items.forEach((item,index)=>{
            if(opt.isEmpty()){
                opt.push(item.clone())
            }
            else{
                var m = HDateRange.merge(opt.peek(),item.clone())
                if(m===null){
                    opt.push(item.clone())
                }
                else{
                    opt.peek().start = m.start
                    opt.peek().end = m.end
                }
            }
        })
        this.items = opt
        return this
    }
    get formatted(){
        var ret = ""
        this.items.forEach((item,index)=>{
            if(ret===""){
                ret = item.formatted
            }
            else{
                ret = ret + "\n" + item.formatted
            }
        })
        return ret
    }
    adopt(other){
        if(other instanceof HDateRanges){
            this.items.addAll(other.items)
        }
    }
    getAt(index){
        if(this.length>index && index>-1){
            return this.items[index]
        }
    }
    static intersect(f, s){
        var fo = f.clone().optimize()
        var so = s.clone().optimize()
        var i = 0, j = 0;
         
        var n = fo.items.length, m = so.items.length;
        var ret = []
        while (i < n && j < m)
        {
            var l = Math.max(fo.items[i].start.date, so.items[j].start.date);
            var r = Math.min(fo.items[i].end.date, so.items[j].end.date);
            if (l <= r){
                ret.push(new HDateRange(l,r))
            }
            if (fo.items[i].end.date < so.items[j].end.date)
                i++;
            else
                j++;
        }
        var r = new HDateRanges()
        r.items = ret
        return r.optimize()
    }
    equals(other){
        if(other instanceof HDateRanges){
            var t = this.clone()
            var o = other.clone()
            t.optimize()
            o.optimize()
            if(t.length===o.length){
                for(var i=0;i<t.length;i++){
                    var tt = t.items[i]
                    var oo = o.items[i]
                    if(!tt.equals(oo)){
                        return false
                    }
                }
                return true
            }
        }
        return false
    }
    includes(other){
        var i = HDateRanges.intersect(this,other)
        return other.equals(i)
    }
}

class HTime{
    constructor(input){
        this.time = 0
        if(typeof input==="string"){
            var parts = input.match(/(\d{1,2}):(\d{1,2})/)
            var h = 0
            var m = 0
            if(parts.length===3){
                h = parseInt(parts[1])
                m = parseInt(parts[2])
                this.time = (h*60 + m)*60000
            }
        }
        if(typeof input==="number"){
            this.time = input
        }
    }
    get formatted(){
        var mints = this.time/60000
        var m = mints%60
        mints -= m
        var h = mints/60
        return `${leftPad(h,2)}:${leftPad(m,2)}`
    }
    clone(){
        return new HTime(this.time)
    }
    equals(other){
        return (
            other instanceof HTime
            &&this.time===other.time)
    }
}
class HTimeRange{
    constructor(input1,input2){
        this.start = 0
        this.end = 0
        if(typeof input1==="string"){
            var parts = input1.split("-")
            if(parts.length>0){
                this.start = new HTime(parts[0])
                this.end = new HTime(parts[parts.length-1])
            }
        }
        if(typeof input2==="string"){
            this.end = new HTime(input2)
        }
        if(typeof input1==="number"){
            this.start = new HTime(input1)
            this.end = this.start.clone();
        }
        if(typeof input2==="number"){
            this.end = new HTime(input2)
        }
        if(input1 instanceof HTime){
            this.start = input1.clone()
            this.end = this.start.clone()
        }
        if(input2 instanceof HTime){
            this.end = input2.clone()
        }
        if(input1 instanceof HTimeRange){
            this.start = input1.start.clone()
            this.end = input1.end.clone()
        }
        if(this.end.time<this.start.time){
            var temp = this.start.time
            this.start.time = this.end.time
            this.end.time = this.start.time
        }
    }
    clone(){
        return new HTimeRange(this.start.clone(),this.end.clone())
    }
    get formatted(){
        return this.start.formatted + " to " + this.end.formatted
    }
    static merge(first,second){
        if(first instanceof HTimeRange && second instanceof HTimeRange){
            var s = Math.max(first.start.time,second.start.time)
            var e = Math.min(first.end.time,second.end.time)
            if(e>=s){
                var start = Math.min(first.start.time,second.start.time)
                var end = Math.max(first.end.time,second.end.time)
                return new HTime(start,end)
            }
        }
        return null
    }
    static intersect(first,second){
        var s = Math.max(first.start.time,second.start.time)
        var e = Math.min(first.end.time,second.end.time)
        if(e>=s){
            return new HTime(s,e)
        }
        return null
    }
    equals(other){
        var se = this.start.equals(other.start)
        var ee = this.end.equals(other.end)
        return (other instanceof HTimeRange)&&se&&ee
    }
    includes(other){
        var i = HTimeRange.intersect(this,other)
        return other.equals(i)
    }
    slots(duration){
        var ret = []
        /////////////////////////////
        if(typeof duration ==='number' && duration>0)
        for(var i=this.start.time;i<=this.end.time;i += duration){
            var s = i 
            var e = s + duration
            if(e<=this.end.time){
                ret.push(new HTimeRange(s,e))
            }
        }
        /////////////////////////////
        var r = new HTimeRanges()
        r.items = ret
        return r
    }
}
class HTimeRanges{
    constructor(...items){
        this.items = []
        items.forEach((item,index)=>{
            if(item instanceof HTimeRange){
                this.items.push(item)
            }
        })
    }
    forEach(callback){
        this.items.forEach((item,index)=>{
            if(typeof callback==='function'){
                callback(item,index)
            }
        })
    }
    peek(){
        if(this.items.length>0){
            return this.items[this.items.length - 1]
        }
        else{
            return null
        }
    }
    isEmpty(){
        return this.items.length===0
    }
    get length(){
        return this.items.length
    }
    push(item){
        if(item instanceof HTimeRange){
            this.items.push(item)
        }
    }
    clone(){
        var ret = []
        this.items.forEach((item,index)=>{
            ret.push(item.clone())
        })
        var r = new HTimeRanges()
        r.items = ret
        return r
    }
    sorted(){
        return this.clone().sort()
    }
    sort(){
        this.items.sort(function(a,b){
            return a.start.time - b.start.time
        })
        return this
    }
    merge(){
        this.sort()
        var stack = []
        this.items.forEach((item,index)=>{
            if(stack.isEmpty() || item.start.time > stack.peek().end.time){
                stack.push(item.clone())
            }
            else{
                if(stack.peek().end.time < item.end.time){
                    stack.peek().end.time = item.end.time
                }
            }
        })
        this.items = stack
        return this
    }
    merged(){
        return this.clone().merge()
    }
    get formatted(){
        var ret = ""
        this.items.forEach((item,index)=>{
            if(ret===""){
                ret = item.formatted
            }
            else{
                ret = ret + "\n" + item.formatted
            }
        })
        return ret
    }
    adopt(other){
        if(other instanceof HTimeRanges){
            this.items.addAll(other.items)
        }
    }
    getAt(index){
        if(this.length>index && index>-1){
            return this.items[index]
        }
    }
    static intersect(f, s){
        var fo = f.clone().merge()
        var so = s.clone().merge()
        var i = 0, j = 0;
         
        var n = fo.items.length, m = so.items.length;
        var ret = []
        while (i < n && j < m)
        {
            var l = Math.max(fo.items[i].start.time, so.items[j].start.time);
            var r = Math.min(fo.items[i].end.time, so.items[j].end.time);
            if (l <= r){
                ret.push(new HTimeRange(l,r))
            }
            if (fo.items[i].end.time < so.items[j].end.time)
                i++;
            else
                j++;
        }
        var r = new HTimeRanges()
        r.items = ret
        return r.merge()
    }
    equals(other){
        if(other instanceof HTimeRanges){
            var t = this.clone()
            var o = other.clone()
            t.merge()
            o.merge()
            if(t.length===o.length){
                for(var i=0;i<t.length;i++){
                    var tt = t.items[i]
                    var oo = o.items[i]
                    if(!tt.equals(oo)){
                        return false
                    }
                }
                return true
            }
        }
        return false
    }
    includes(other){
        var i = HTimeRanges.intersect(this,other)
        return other.equals(i)
    }
    sessions(duration){
        var t = this.clone()
        var ret = []
        //////////////////////////
        if(typeof duration === 'number'){
            t.items.forEach((item,index)=>{
                var slots = item.slots(duration)
                ret.addAll(slots.items)
            })
        }
        //////////////////////////
        var r = new HTimeRanges()
        r.items = ret
        return r
    }
}

class HAvailability{
    constructor(dateRange,timeRanges){
        this.dateRange = new HDateRange()
        this.timeRanges = new HTimeRanges()
        if(dateRange instanceof HDateRange && timeRanges instanceof HTimeRanges){
            this.dateRange = dateRange
            this.timeRanges = timeRanges
        }
    }
    clone(){
        return new HAvailability(this.dateRange.clone(),this.timeRanges.clone())
    }
    get formatted(){
        return this.dateRange.formatted+"\n"+this.timeRanges.formatted.split("\n").map((item,index)=>{
            return "\t"+item
        }).join("\n")
    }
    optimize(){
        this.timeRanges.merge()
        return this;
    }
    static intersect(first,second){
        var f = first.clone()
        var s = second.clone()
        var d = HDateRange.intersect(f.dateRange,s.dateRange)
        if(d===null){
            return null
        }
        var t = HTimeRanges.intersect(f.timeRanges,s.timeRanges)
        if(t===null||t.length===0){
            return null
        }
        return new HAvailability(d,t)
    }
    equals(other){
        if(other instanceof HAvailability){
            var t = this.clone()
            var o = other.clone()
            t.optimize()
            o.optimize()
            var de = t.dateRange.equals(o.dateRange)
            var te = t.timeRanges.equals(o.timeRanges)
            return de&&te
        }
        return false
    }
    includes(other){
        var i = HAvailability.intersect(this,other)
        return other.equals(i)
    }
    buildSessions(duration){
        var ret = []
        var dr = this.dateRange.clone()
        var tr = this.timeRanges.clone()
        var sessions = tr.sessions(duration)
        if(typeof duration==='number' && duration>0){
            dr.forEachDate((date,index)=>{
                var dr = new HDateRange(date.clone(),date.clone())
                var a = new HAvailability(dr,sessions)
                ret.push(a)
            })
        }
        var ha = new HAvailabilities()
        ha.items = ret
        return ha
    }
}
class HAvailabilities{
    constructor(...items){
        this.items = []
        items.forEach((item,index)=>{
            if(item instanceof HAvailability){
                this.items.push(item)
            }
        })
    }
    forEach(callback){
        this.items.forEach((item,index)=>{
            if(typeof callback==='function'){
                callback(item,index)
            }
        })
    }
    peek(){
        if(this.items.length>0){
            return this.items[this.items.length - 1]
        }
        else{
            return null
        }
    }
    isEmpty(){
        return this.items.length===0
    }
    get length(){
        return this.items.length
    }
    push(item){
        if(item instanceof HAvailability){
            this.items.push(item)
        }
    }
    clone(){
        var ret = []
        this.items.forEach((item,index)=>{
            ret.push(item.clone())
        })
        var r = new HAvailabilities()
        r.items = ret
        return r
    }
    getAt(index){
        if(this.length>index && index>-1){
            return this.items[index]
        }
    }
    get formatted(){
        var ret = ""
        this.items.forEach((item,index)=>{
            if(ret===""){
                ret = item.formatted
            }
            else{
                ret = ret + "\n" + item.formatted
            }
        })
        return ret
    }
    sort(){
        this.items.sort(function(a,b){
            return a.dateRange.start.date - b.dateRange.start.date
        })
    }
    optimize(){
        //optimize time ranges
        this.items.forEach((item,index)=>{
            item.timeRanges.merge()
        })
        
        //sort as date
        this.sort()
        
        
        const numbers = new Set();
    
        for(var i=0;i<this.items.length;i++){
          var item = this.items[i];
          numbers.add(item.dateRange.start.date);
          numbers.add(item.dateRange.end.date);
        }
        
        var cutters = [...numbers].sort(function(a, b) {
          return a - b;
        });
        /////////////////////////////////////
        var splitted = [];
        function splitRange(range,cutters){
            var start = range.dateRange.start.date;
            var end = range.dateRange.end.date;
            var times = range.timeRanges;
            
            var startIndex = cutters.indexOf(start)
            var endIndex = cutters.indexOf(end)
            var count = endIndex - startIndex + 1
            var myCutters = cutters.slice(startIndex,endIndex + 1)
            var ret = [];
            if(count===1){
                var first = myCutters[0];
                var pp = new HAvailability(
                    new HDateRange(new HDate(first),new HDate(first)),
                    times.clone()
                )
                ret.push(pp)
            }
            else{
                for(var i=0;i<count-1;i++){
                    var first = myCutters[i];
                    var last = myCutters[i+1];
                    if(i===0){
                        var pp = new HAvailability(
                            new HDateRange(new HDate(first),new HDate(first)),
                            times.clone()
                        )
                        ret.push(pp)
                    }
                    var f = first + HDate.DAY_VALUE
                    var l = last - HDate.DAY_VALUE
                    if(f<=l&&f<last&&l>first){
                        var pp = new HAvailability(
                            new HDateRange(new HDate(f),new HDate(l)),
                            times.clone()
                        )
                        ret.push(pp)
                    }
                        var pp = new HAvailability(
                            new HDateRange(new HDate(last),new HDate(last)),
                            times.clone()
                        )
                    ret.push(pp)
                }
            }
            return ret;
        }
        for(var i=0;i<this.items.length;i++){
            var item = this.items[i];
            var s = splitRange(item,cutters)
            splitted.addAll(s);
        }
        splitted.sort(function(a,b){
            return a.dateRange.start.date - b.dateRange.start.date
        })
        
        
        var ret = [];
        function mergeTimes(times) {
            if (!Array.isArray(times)) {
                return [];
            }
        
            var count = times.length;
        
            if (count === 0) {
                return [];
            }
        
            times.sort(function(a, b) {
                return a.start.time - b.start.time;
            });
        
            var ret = [];
        
            for (var i = 0; i < count; i++) {
                var timeRange = times[i];
                if (ret.isEmpty() || timeRange.start.time > ret.peek().end.time) {
                    ret.push(timeRange);
                }
                if (ret.peek().end.time < timeRange.end.time) {
                    ret.peek().end.time = timeRange.end.time;
                }
            }
            return ret
        }
        for(var i=0;i<splitted.length;i++){
          var range = splitted[i];
          
          ////////////////////////////////////////
          if(ret.isEmpty()){
              ret.push(range);
          }
          else{
              if(ret.peek().dateRange.start.date===range.dateRange.start.date
              &&ret.peek().dateRange.end.date===range.dateRange.end.date){
                  
                  ret.peek().timeRanges.items = mergeTimes([...ret.peek().timeRanges.items,...range.timeRanges.items])
              }
              else{
                  ret.push(range);
              }
          }
        }
        /////////////////////////////
        var ret2 = [];
        ////////////////
        function timeRangeArraysEqual(first, second) {
            var fc = first.length;
            var sc = second.length;
            if (fc !== sc) {
                return false;
            }
            for (var i = 0; i < first.length; i++) {
                var f = first.items[i];
                var s = second.items[i];
                if (f.start.time !== s.start.time || f.end.time !== s.end.time) {
                    return false;
                }
            }
            return true;
        }
        ////////////////
        for (var i = 0; i < ret.length; i++) {
            var item = ret[i];
            if (ret2.isEmpty()) {
                ret2.push(item);
            }
            else {
                var last = ret2.peek();
                var gap = item.dateRange.start.date - last.dateRange.end.date
                if (gap === HDate.DAY_VALUE) {
                    if (timeRangeArraysEqual(last.timeRanges, item.timeRanges)) {
                        ret2.peek().dateRange.end = item.dateRange.end;
                        ret2.peek().timeRanges = item.timeRanges;
                    }
                    else {
                        ret2.push(item);
                    }
                }
                else {
                    ret2.push(item);
                }
            }
        }
        this.items = ret2;
        return this;
    }
    static intersect(first,second){
        var f = first.clone().optimize()
        var s = second.clone().optimize()
        var i = 0, j = 0;
         
        var n = f.length, m = s.length;
        var ret = []
        while (i < n && j < m)
        {
             
            // Left bound for intersecting segment
            var l = Math.max(f.items[i].dateRange.start.date, s.items[j].dateRange.start.date);
     
            // Right bound for intersecting segment
            var r = Math.min(f.items[i].dateRange.end.date, s.items[j].dateRange.end.date);
             
            // If segment is valid print it
            if (l <= r){
                var t1 = f.items[i].timeRanges
                var t2 = s.items[j].timeRanges
                var t = HTimeRanges.intersect(t1,t2)
                if(t!==null){
                    ret.push(new HAvailability(
                            new HDateRange(l,r),
                            t
                        ))
                }
            }
            if (f.items[i].dateRange.end.date < s.items[j].dateRange.end.date)
                i++;
            else
                j++;
        }
        var h = new HAvailabilities()
        h.items = ret
        return h
    }
    equals(other){
        if(other instanceof HAvailabilities){
            var t = this.clone()
            var o = other.clone()
            t.optimize()
            o.optimize()
            if(t.length===o.length){
                for(var i=0;i<t.length;i++){
                    var tt = t.items[i]
                    var oo = o.items[i]
                    if(!tt.equals(oo)){
                        return false
                    }
                }
                return true
            }
        }
        return false
    }
    includes(other){
        var i = HAvailabilities.intersect(this,other)
        return other.equals(i)
    }
    buildSessions(required,duration){
        var r = new HAvailabilities()
        var ret = []
        if(required instanceof HAvailabilities){
            var i = HAvailabilities.intersect(this,required)
            i.items.forEach((item,index)=>{
                var a = item.buildSessions(duration)
                ret.addAll(a.items)
            })
        }
        r.items = ret
        return r
    }
}
